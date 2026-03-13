<?php namespace Dever;

use Dever;

class Sql
{
    // 1=mysql 2=pgsql
    private int $type = 1;

    // MySQL uses backticks, PG uses double quotes
    private string $link = '`';

    public function setType($type)
    {
        $this->type = intval($type);
        $this->link = ($this->type === 2) ? '"' : '`';
    }

    private function isPg(): bool
    {
        return $this->type === 2;
    }

    // Quote identifier (table/column). Supports "t.id"
    private function q(string $ident): string
    {
        $ident = trim($ident);
        if ($ident === '' || $ident === '*') return $ident;

        // already quoted with current quote char
        if ($ident[0] === $this->link && substr($ident, -1) === $this->link) {
            return $ident;
        }

        if (strpos($ident, '.') !== false) {
            [$a, $b] = explode('.', $ident, 2);
            $a = trim($a, "`\" ");
            $b = trim($b, "`\" ");
            return $a . '.' . $this->link . $b . $this->link;
        }

        $ident = trim($ident, "`\" ");
        return $this->link . $ident . $this->link;
    }

    private function unq(string $ident): string
    {
        return str_replace(['`','"'], '', $ident);
    }

    // 兼容 where 条件：数组可能是操作符格式或普通 ID 列表
    private function normalizeOpValue(array $arr): array
    {
        if (isset($arr[0]) && is_string($arr[0])) {
            $op = strtolower(trim($arr[0]));
            $ops = ['=', '>', '<', '>=', '<=', '!=', '<>', 'in', 'not in', 'like', 'between', 'group', 'json_contains'];
            if (in_array($op, $ops, true)) {
                return [$arr[0], $arr[1] ?? null];
            }
        }
        return ['in', $arr];
    }

    // ---- PG inserts helpers ----

    // Normalize field list like: id,name,`key`,"partition", t.id  => quoted list for current dialect
    private function normalizeFieldList(string $fieldList): string
    {
        $parts = array_filter(array_map('trim', explode(',', $fieldList)));
        $out = [];

        foreach ($parts as $p) {
            $p = trim($p);
            $p = trim($p, "`\" ");

            // keep raw expressions best-effort (functions, *)
            if (preg_match('/\w+\s*\(.*\)/u', $p) || str_contains($p, '*')) {
                $out[] = $p;
                continue;
            }

            $out[] = $this->q($p);
        }

        return implode(',', $out);
    }

    // Convert a single value token into a PG-safe literal (or keep numeric)
    private function pgLiteral($v): string
    {
        if ($v === null) return 'NULL';

        // keep numeric as-is
        if (is_int($v) || is_float($v) || (is_string($v) && preg_match('/^-?\d+(\.\d+)?$/', $v))) {
            return (string)$v;
        }

        $s = (string)$v;
        $s = str_replace("'", "''", $s);
        return "'" . $s . "'";
    }

    // Convert MySQL-style row string: 1,"平台系统","abc" => 1,'平台系统','abc'
    // Also handles already-single-quoted strings and NULL/numbers best-effort.
    private function normalizePgValueRow(string $row): string
    {
        $row = trim($row);

        // If the row already looks like it uses single quotes, leave it alone (best-effort)
        // But still fix any remaining "..." tokens.
        $row = preg_replace_callback('/"([^"]*)"/u', function ($m) {
            return $this->pgLiteral($m[1]);
        }, $row);

        return $row;
    }

    // Build a VALUES row from array values, quoted properly for PG
    private function buildPgRowFromArray(array $vals): string
    {
        $out = [];
        foreach ($vals as $v) {
            $out[] = $this->pgLiteral($v);
        }
        return implode(',', $out);
    }

    // ----------------------------

    public function desc($table)
    {
        if ($this->isPg()) {
            $t = $this->unq($table);
            return "SELECT column_name AS \"Field\",
                           data_type AS \"Type\",
                           is_nullable AS \"Null\",
                           column_default AS \"Default\"
                    FROM information_schema.columns
                    WHERE table_schema = current_schema()
                      AND table_name = '{$t}'
                    ORDER BY ordinal_position";
        }
        return 'DESC ' . $this->q($table);
    }

    public function truncate($table)
    {
        if ($this->isPg()) {
            return 'TRUNCATE TABLE ' . $this->q($table) . ' RESTART IDENTITY';
        }
        return 'TRUNCATE TABLE ' . $this->q($table);
    }

    public function optimize($table)
    {
        if ($this->isPg()) {
            return 'VACUUM (ANALYZE) ' . $this->q($table);
        }
        return 'OPTIMIZE TABLE ' . $this->q($table);
    }

    public function analyze($table)
    {
        if ($this->isPg()) {
            return 'ANALYZE ' . $this->q($table);
        }
        return 'ANALYZE TABLE ' . $this->q($table);
    }

    public function explain($sql)
    {
        return 'EXPLAIN ' . $sql;
    }

    public function showIndex($table)
    {
        if ($this->isPg()) {
            $t = $this->unq($table);
            return "SELECT indexname AS \"Key_name\",
                           indexdef  AS \"Index_def\"
                    FROM pg_indexes
                    WHERE schemaname = current_schema()
                      AND tablename = '{$t}'";
        }
        return 'SHOW INDEX FROM ' . $this->q($table);
    }

    private function mapPgType(string $mysqlType): string
    {
        $t = strtolower($mysqlType);

        if (str_contains($t, 'jsonb')) return 'JSONB';
        if (str_contains($t, 'json')) return 'JSON';

        if (str_contains($t, 'bigint')) return 'BIGINT';
        if (str_contains($t, 'tinyint')) return 'SMALLINT';
        if (str_contains($t, 'int')) return 'INTEGER';

        if (str_contains($t, 'double')) return 'DOUBLE PRECISION';
        if (str_contains($t, 'float')) return 'REAL';
        if (str_contains($t, 'decimal')) return strtoupper($mysqlType);

        if (str_contains($t, 'longtext') || str_contains($t, 'text')) return 'TEXT';

        if (str_contains($t, 'varchar')) return strtoupper($mysqlType);
        if (str_contains($t, 'char')) return strtoupper($mysqlType);

        return strtoupper($mysqlType);
    }

    public function create($config)
    {
        if (isset(Dever::config('setting')['database']['create']) && !Dever::config('setting')['database']['create']) {
            return;
        }

        $table = $config['table'];

        $struct = [
            'id'    => ['name' => 'ID',    'type' => 'bigint'],
            'cdate' => ['name' => 'cdate', 'type' => 'bigint'],
        ];
        $struct = array_merge($struct, $config['struct']);

        $sqls = [];
        $sqls[] = 'DROP TABLE IF EXISTS ' . $this->q($table);

        $cols = [];
        foreach ($struct as $k => $v) {
            $cols[] = $this->createField($k, $v);
        }

        $create = 'CREATE TABLE IF NOT EXISTS ' . $this->q($table) . "(\n  " . implode(",\n  ", $cols) . "\n)";

        if (!$this->isPg()) {
            if (isset($config['auto'])) {
                $create .= ' AUTO_INCREMENT = ' . intval($config['auto']);
            }
            if (isset($config['type'])) {
                $create .= ' ENGINE = ' . $config['type'] . ' ';
            }
            if (isset($config['name'])) {
                $create .= ' COMMENT="' . addslashes($config['name']) . '"';
            }
            $sqls[] = $create;
            return implode(';', $sqls);
        }

        $sqls[] = $create;

        if (!empty($config['name'])) {
            $sqls[] = "COMMENT ON TABLE " . $this->q($table) . " IS '" . addslashes($config['name']) . "'";
        }
        foreach ($struct as $k => $v) {
            if (!empty($v['name'])) {
                $sqls[] = "COMMENT ON COLUMN " . $this->q($table) . "." . $this->q($k) . " IS '" . addslashes($v['name']) . "'";
            }
        }

        return implode(';', $sqls);
    }

    public function createField($name, $set)
    {
        if ($this->isPg()) {
            $col = $this->q($name);

            // 主键都是 id：用 identity
            if ($name === 'id') {
                return $col . ' BIGINT GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY';
            }

            $type = $this->mapPgType($set['type']);
            $field = $col . ' ' . $type;

            $rawType = strtolower($set['type']);
            if (str_contains($rawType, 'json')) {
                $cast = str_contains($rawType, 'jsonb') ? 'jsonb' : 'json';
                if (isset($set['default'])) {
                    $def = $set['default'];
                    if (is_array($def) || is_object($def)) {
                        $def = json_encode($def, JSON_UNESCAPED_UNICODE);
                    }
                    $field .= " NOT NULL DEFAULT '" . addslashes((string)$def) . "'::{$cast}";
                } else {
                    $field .= " NOT NULL DEFAULT '{}'::{$cast}";
                }
                return $field;
            }

            if (str_contains(strtolower($set['type']), 'text')) {
                $field .= ' NULL';
            } elseif (isset($set['default'])) {
                $def = $set['default'];
                if (is_numeric($def)) {
                    $field .= ' NOT NULL DEFAULT ' . $def;
                } else {
                    $field .= " NOT NULL DEFAULT '" . addslashes((string)$def) . "'";
                }
            } elseif (preg_match('/(int|float|decimal|double)/i', $set['type'])) {
                $field .= ' NOT NULL DEFAULT 0';
            } else {
                $field .= " NOT NULL DEFAULT ''";
            }

            return $field;
        }

        // MySQL 原逻辑
        $field = '`' . $name . '` ' . strtoupper($set['type']);
        if ($name == 'id') {
            $field .= ' UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL';
        } elseif (strpos($set['type'], 'text') !== false) {
            $field .= ' NULL';
        } elseif (isset($set['default'])) {
            $field .= ' NOT NULL DEFAULT "' . $set['default'] . '"';
        } elseif (strpos($set['type'], 'int') !== false || strpos($set['type'], 'float') !== false || strpos($set['type'], 'decimal') !== false || strpos($set['type'], 'double') !== false) {
            $field .= ' NOT NULL DEFAULT 0';
        } else {
            $field .= ' NOT NULL DEFAULT ""';
        }
        if (isset($set['name'])) {
            $field .= ' COMMENT \'' . $set['name'] . '\'';
        }
        return $field;
    }

    public function alter($table, $struct, $data)
    {
        if ($this->isPg()) {
            $sql = [];
            $t = $this->q($table);

            $existing = [];
            foreach ($data as $v) {
                $existing[$v['Field']] = $v;
            }

            foreach ($existing as $field => $v) {
                if ($field === 'id' || $field === 'cdate') continue;
                if (!isset($struct[$field])) {
                    $sql[] = "ALTER TABLE {$t} DROP COLUMN " . $this->q($field);
                }
            }

            foreach ($struct as $k => $v) {
                if ($k === 'id' || $k === 'cdate') continue;
                if (!isset($existing[$k])) {
                    $sql[] = "ALTER TABLE {$t} ADD COLUMN " . $this->createField($k, $v);
                }
            }

            return implode(';', $sql);
        }

        // MySQL 原逻辑
        $sql = [];
        $alter = 'ALTER TABLE `' . $table . '` ';
        foreach ($data as $v) {
            $field = $v['Field'];
            if ($field != 'id' && $field != 'cdate') {
                $set = $struct[$field] ?? false;
                if ($set) {
                    if ($set['type'] != $v['Type'] || (isset($set['default']) && $set['default'] != $v['Default'])) {
                        $sql[] = $alter . ' CHANGE `' . $field . '` ' . $this->createField($field, $set);
                    } else {
                        unset($struct[$field]);
                    }
                } else {
                    $sql[] = $alter . ' DROP `' . $field . '`';
                }
            }
        }
        if ($struct) {
            foreach ($struct as $k => $v) {
                $sql[] = $alter . ' ADD ' . $this->createField($k, $v);
            }
        }
        return implode(';', $sql);
    }

    public function index($table, $index, $del = [])
    {
        if ($this->isPg()) {
            $sql = [];
            $t = $this->q($table);

            // 删除旧索引/约束
            foreach ($del as $v) {
                $name = $v['Key_name'] ?? '';
                if (!$name) {
                    continue;
                }

                if (preg_match('/_pkey$/i', $name)) {
                    continue;
                }

                // 普通索引删除
                $sql[] = 'DROP INDEX IF EXISTS ' . $this->q($name);
            }
            if ($index) {
                foreach ($index as $k => $v) {
                    $tpe = 'INDEX';
                    if (strpos($v, '.')) {
                        [$v, $tpe] = explode('.', $v, 2);
                    }
                    $unique = (strtoupper($tpe) === 'UNIQUE') ? 'UNIQUE ' : '';
                    $fields = $this->normalizeFieldList($v);
                    $sql[] = "CREATE {$unique}INDEX IF NOT EXISTS " . $this->q($k) . " ON {$t} ({$fields})";
                }
            }

            return implode(';', $sql);
        }

        // MySQL 原逻辑
        $sql = [];
        $alter = 'ALTER TABLE `' . $table . '` ';
        foreach ($del as $v) {
            if ($v['Key_name'] != 'PRIMARY') {
                $sql[] = $alter . ' DROP INDEX ' . $v['Key_name'];
            }
        }
        if ($index) {
            foreach ($index as $k => $v) {
                $t = 'INDEX';
                if (strpos($v, '.')) {
                    list($v, $t) = explode('.', $v);
                }
                $sql[] = $alter . ' ADD ' . strtoupper($t) . ' ' . $k . ' (' . $v . ')';
            }
        }
        return implode(';', $sql);
    }


    public function partition($table, $partition, $index)
    {
        if ($this->isPg()) {
            return '';
        }

        // MySQL 原逻辑（保留）
        $state = true;
        foreach ($index as $k => $v) {
            if ($v['Key_name'] == 'PRIMARY' && $v['Column_name'] == $partition['field']) {
                $state = false;
            }
        }
        $alter = '';
        if ($state) {
            $type = $partition['type'];
            if ($partition['type'] == 'time') {
                $type = 'range';
            }
            $alter = 'ALTER TABLE `' . $table . '` DROP PRIMARY KEY, ADD PRIMARY KEY (`id`, `'.$partition['field'].'`) USING BTREE;ALTER TABLE `' . $table . '` PARTITION BY '.strtoupper($type).' ('.$partition['field'].') ';
        } else {
            $alter = 'ALTER TABLE `' . $table . '` ADD PARTITION ';
        }
        if ($partition['type'] == 'range' || $partition['type'] == 'time') {
            $name = $partition['value'];
            if ($partition['type'] == 'time') {
                $name = date('Ymd', $name - 86400);
            }
            $sql = 'PARTITION p'.$name.' VALUES LESS THAN ('.$partition['value'].')';
            return $alter . '('.$sql.')';
        } elseif ($partition['type'] == 'list') {
            $sql = [];
            foreach ($partition['value'] as $k => $v) {
                $k = str_replace('-', '_', $v);
                $sql[] = 'PARTITION p'.$k.' VALUES IN ('.$v.')';
            }
            $sql = implode(',', $sql);
            return $alter . '('.$sql.')';
        } elseif ($partition['type'] == 'hash' || $partition['type'] == 'key') {
            if ($state) {
                return $alter . 'PARTITIONS ' . $partition['value'];
            }
            return $this->desc($table);
        }
    }

    public function select($table, $param, &$bind, $set = [], $field = [], $version = false, $type = '')
    {
        $col = $set['col'] ?? '*';
        if ($this->isPg() && is_string($col)) {
            $col = str_replace('`', '"', $col);
        }
        $rule = '';

        if (isset($set['join'])) {
            $table .= ' AS main';
            foreach ($set['join'] as $k => $v) {
                $table .= ' ' . $v['type'] . ' ' . DEVER_PROJECT . '_' . $v['table'] . ' AS t' . $k . ' ON ' . $v['on'];
            }
        }
        if (isset($set['group'])) {
            $rule .= ' GROUP BY ' . $set['group'];
        }
        if (isset($set['order'])) {
            if ($type == 'Influxdb') {
                $set['order'] = ' time desc';
            }
            $rule .= ' ORDER BY ' . $set['order'];
        }

        if (isset($set['limit'])) {
            if (is_array($set['limit'])) {
                $offset = intval($set['limit'][0] ?? 0);
                $count  = intval($set['limit'][1] ?? 0);

                if ($type === 'Influxdb') {
                    $rule .= " LIMIT {$count} OFFSET {$offset}";
                } elseif (!$type) {
                    $innerLimit = $this->isPg()
                        ? " LIMIT {$count} OFFSET {$offset}"
                        : " limit {$offset},{$count}";

                    $table .= ' inner join (select id from ' . $table . $this->where($param, $bind, $field) . $rule . $innerLimit . ') as t on '.$table.'.id=t.id';
                    $rule = '';
                    $param = false;
                } else {
                    $rule .= " LIMIT {$count} OFFSET {$offset}";
                }
            } else {
                if ($type == 'Influxdb' && strpos($set['limit'], ',')) {
                    $temp = explode(',', $set['limit']);
                    $rule .= ' LIMIT ' . $temp[1] . ' OFFSET ' . $temp[0];
                } else {
                    if ($this->isPg() && is_string($set['limit']) && strpos($set['limit'], ',') !== false) {
                        [$o, $c] = array_map('intval', explode(',', $set['limit'], 2));
                        $rule .= " LIMIT {$c} OFFSET {$o}";
                    } else {
                        $rule .= ' LIMIT ' . $set['limit'];
                    }
                }
            }
        }

        if ($version) {
            $rule .= ' FOR UPDATE';
        }

        return 'SELECT ' . $col . ' FROM ' . $table . $this->where($param, $bind, $field, $type) . $rule;
    }

    public function where($param, &$bind, $field = [], $type = '')
    {
        if (is_string($param)) {
            $trim = ltrim($param);
            if ($trim === '') {
                return '';
            }
            if (preg_match('/^(order|group)\s+by\b/i', $trim) || preg_match('/^limit\b/i', $trim)) {
                return ' ' . $trim;
            }
        }
        if ($param || is_numeric($param)) {
            $first = $second = '';
            if (is_array($param)) {
                $i = 0;
                foreach ($param as $k => $v) {
                    if (strpos($k, '#')) {
                        $k = trim($k, '#');
                    }
                    if ($k == 'or' || $k == 'and') {
                        $first_link = $second_link = '';
                        foreach ($v as $k1 => $v1) {
                            if (is_array($v1)) {
                                [$sym, $val] = $this->normalizeOpValue($v1);
                                $this->field($second_link, $bind, $i, $k1, $sym, $val, $field, $type);
                            } else {
                                $this->field($first_link, $bind, $i, $k1, '=', $v1, $field, $type);
                            }
                        }
                        $second .= ' ' . $k . ' (' . $this->replace($first_link) . $second_link . ')';
                    } else {
                        if (is_array($v)) {
                            [$sym, $val] = $this->normalizeOpValue($v);
                            $this->field($second, $bind, $i, $k, $sym, $val, $field, $type);
                        } else {
                            $this->field($first, $bind, $i, $k, '=', $v, $field, $type);
                        }
                    }
                }
                $cond = $this->replace($first . $second);
                if (trim($cond) === '') {
                    return '';
                }
                return ' WHERE ' . $cond;
            } elseif (is_numeric($param)) {
                if ($type == 'Influxdb') {
                    return ' WHERE "id" = \'' . $param . '\'';
                }
                return ' WHERE id = ' . intval($param);
            }
            return ' WHERE ' . $param;
        }
        return '';
    }

    private function field(&$sql, &$bind, &$num, $key, $symbol, $value, $field, $type)
    {
        if (is_array($key) || is_object($key)) {
            return;
        }
        $key = (string)$key;

        $prefix = '';
        if (strstr($key, '.')) {
            $temp = explode('.', $key);
            $key = $temp[1];
            $prefix = $temp[0] . '.';
        } elseif ($field && empty($field[$key]) && strpos('id,cdate', $key) === false) {
            return;
        }

        if (is_array($symbol)) {
            $symbol = $symbol[0] ?? '=';
        }
        if (!is_string($symbol) || $symbol === '') {
            $symbol = '=';
        }

        // ✅ PG：如果传入的是空字符串，直接跳过这个条件（避免 bigint = '' 报错）
        // 说明：like/group/JSON_CONTAINS/between 这些场景不一定是数值，所以不在这里一刀切处理
        if ($this->isPg() && is_string($value) && $value === '') {
            // in 场景留到下面单独处理（因为可能是数组/逗号串）
            if (strpos($symbol, 'in') === false && $symbol !== 'between' && $symbol !== 'like'
                && $symbol !== 'group' && $symbol !== 'JSON_CONTAINS') {
                return;
            }
        }

        $sql .= ' and ';

        $state = false;
        $index = '';
        $link = $this->isPg() ? '"' : '`';

        if ($type == 'Influxdb') {
            if ($key == 'cdate') {
                $key = 'time';
                $value = date('Y-m-d H:i:s', $value - 28800);
            }
            $link = '"';
            if (strpos($symbol, 'in') !== false) {
                $symbol = '=';
            }
        } elseif (is_string($value) && stristr($value, 'select ')) {
            $index = $value;
        } elseif (is_array($bind)) {
            $state = true;
            $index = ':' . $key . $num;
        }

        if ($state == false && $index == '') {
            $index = "'" . str_replace("'", "''", (string)$value) . "'";
        }

        $keySql = $prefix . $link . $key . $link;

        if (strpos($symbol, 'in') !== false) {
            if ($state) {
                if (!is_array($value)) {
                    $value = explode(',', (string)$value);
                }

                // ✅ PG：过滤掉空字符串，避免 bigint IN ('') 报错
                if ($this->isPg()) {
                    $value = array_values(array_filter($value, function ($vv) {
                        return !($vv === '' || $vv === null);
                    }));
                    if (!$value) {
                        // IN 空集合：直接跳过条件
                        // 也可以改成 $sql .= '1=0' 看你业务语义
                        $sql = rtrim($sql, ' and ');
                        return;
                    }
                }

                $in = '';
                foreach ($value as $k => $v) {
                    if ($k > 0) {
                        $k2 = $index . '_' . $k;
                        $in .= ',' . $k2;
                        $bind[$k2] = $v;
                    } else {
                        $value = $v;
                        $in .= $index;
                    }
                }
            } else {
                $in = $index;
            }
            $sql .= $keySql . ' ' . $symbol . ' (' . $in . ')';

        } elseif ($symbol == 'like') {
            $fn = $this->isPg() ? 'strpos' : 'instr';
            $sql .= $fn . '(' . $keySql . ',' . $index . ') > 0';

        } elseif ($symbol == 'group') {
            if ($this->isPg()) {
                $sql .= "strpos(',' || " . $keySql . " || ',', " . $index . ") > 0";
            } else {
                $sql .= 'instr(concat(",",'.$keySql.',","),'.$index.')' . ' = 1';
            }

        } elseif ($symbol == 'JSON_CONTAINS') {
            if ($this->isPg()) {
                $needle = $state ? $index : ("'" . json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "'");
                $sql .= '(' . $keySql . '::jsonb @> ' . $needle . '::jsonb)';
            } else {
                $sql .= 'JSON_CONTAINS(' . $keySql . ', "' . $index . '", "$")';
            }

        } elseif ($symbol == 'between') {
            if ($this->isPg()) {
                $a = $value[0] ?? null;
                $b = $value[1] ?? null;
                if ($a === '' || $b === '' || $a === null || $b === null) {
                    // PG 避免 bigint/date between 传空字符串导致报错
                    $sql = rtrim($sql, ' and ');
                    return;
                }
            }
            if ($state) {
                $bind[$index . '_e'] = $value[1];
                $value = $value[0];
                $sql .= $keySql . ' between ' . $index . ' and ' . $index . '_e';
            } else {
                $sql .= $keySql . " between '" . $value[0] . "' and '" . $value[1] . "'";
            }

        } else {
            $sql .= $keySql . $symbol . $index;
        }

        if ($state) {
            $bind[$index] = $value;
        }
        $num++;
    }


    public function insert($table, $data, &$bind, $field)
    {
        if ($this->isPg()) {
            $cols = [];
            $vals = [];

            foreach ($data as $k => $v) {
                if ($v === null || ($v === '' && $v !== '0')) {
                    continue;
                }
                if ($field && empty($field[$k]) && strpos('id,cdate', $k) === false) {
                    continue;
                }
                $cols[] = $this->q($k);
                $vals[] = ':' . $k;
                $bind[':' . $k] = $v;
            }

            return 'INSERT INTO ' . $this->q($table) .
                   ' (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ') RETURNING id';
        }

        $sql = 'INSERT INTO `' . $table . '` SET ';
        foreach ($data as $k => $v) {
            if (!$v && $v !== 0) {
                continue;
            }
            if ($field && empty($field[$k]) && strpos('id,cdate', $k) === false) {
                continue;
            }
            $sql .= '`' . $k . '`=:' . $k . ',';
            $bind[':' . $k] = $v;
        }
        return rtrim($sql, ',');
    }

    public function inserts($table, $param)
    {
        if ($this->isPg()) {
            $num = $param['num'] ?? 1;

            // fields might contain `...` from mysql
            $fields = $this->normalizeFieldList($param['field']);

            $sql = 'INSERT INTO ' . $this->q($table) . ' (' . $fields . ') VALUES ';
            $insert = [];

            foreach ($param['value'] as $v) {
                if (is_array($v)) {
                    // array values => build safe literal list: 1,'xx','yy'
                    $row = $this->buildPgRowFromArray($v);
                } else {
                    // string row => convert "xx" => 'xx'
                    $row = $this->normalizePgValueRow((string)$v);
                }

                for ($i = 1; $i <= $num; $i++) {
                    $insert[] = '(' . $row . ')';
                }
            }

            $sql .= implode(',', $insert);

            if (!empty($param['conflict'])) {
                $conflict = $this->normalizeFieldList($param['conflict']);
                $sql .= ' ON CONFLICT (' . $conflict . ') DO NOTHING';
            }

            return $sql;
        }

        // MySQL original
        $num = $param['num'] ?? 1;
        $sql = 'INSERT IGNORE INTO `' . $table . '` (' . $param['field'] . ') VALUES ';
        $insert = [];
        foreach ($param['value'] as $v) {
            if (is_array($v)) {
                $v = '"' . implode('","', $v) . '"';
            }
            for ($i = 1; $i <= $num; $i++) {
                $insert[] = '(' . $v . ')';
            }
        }
        $sql .= implode(',', $insert) . ',';
        return rtrim($sql, ',');
    }

    public function update($table, $param, $data, &$bind, $field)
    {
        $sql = 'UPDATE ' . ($this->isPg() ? $this->q($table) : '`' . $table . '`') . ' SET ';
        foreach ($data as $k => $v) {
            if ($field && empty($field[$k]) && strpos('id,cdate', $k) === false) {
                continue;
            }
            $a = '';
            if (is_array($v)) {
                if (isset($v[2])) {
                    $a = $this->q($v[0]) . $v[1] . $this->q($v[2]);
                    $sql .= $this->q($k) . '=' . $a . ',';
                    continue;
                } elseif (isset($v[1])) {
                    $a = $this->q($k) . $v[0];
                    $v = $v[1];
                } else {
                    $v = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }
            $sql .= $this->q($k) . '=' . $a . ':' . $k . ',';
            $bind[':' . $k] = $v;
        }
        return rtrim($sql, ',') . $this->where($param, $bind, $field);
    }

    public function delete($table, $param, &$bind, $field)
    {
        return 'DELETE FROM ' . ($this->isPg() ? $this->q($table) : '`' . $table . '`') . $this->where($param, $bind, $field);
    }

    public function copy($table, $dest, $param, &$bind, $field)
    {
        $on = 'a.' . $this->q($field[0]) . ' = b.' . $this->q($field[0]);
        $insert = $keys = [];
        foreach ($field as $v) {
            $insert[] = $this->q($v);
            $keys[] = 'a.' . $this->q($v);
        }
        $keys = implode(',', $keys);
        $insert = implode(',', $insert);

        $dest = DEVER_PROJECT . '_' . $dest;

        $sql = 'INSERT INTO ' . $this->q($table) . '(' . $insert . ') SELECT ' . $keys .
            ' FROM ' . $dest . ' a LEFT JOIN ' . $this->q($table) . ' b ON ' . $on .
            ' ' . $this->where($param, $bind, []) .
            ' AND b.' . $this->q($field[0]) . ' IS NULL';

        return $sql;
    }

    public function distance($lng, $lat)
    {
        if ($this->isPg()) {
            return 'round((ST_Distance(ST_MakePoint(lng, lat)::geography, ST_MakePoint('.$lng.', '.$lat.')::geography))/1000, 2) as distance';
        }
        return 'round((st_distance(point(lng, lat), point('.$lng.', '.$lat.'))*111195)/1000, 2) as distance';
    }

    private function replace($string)
    {
        if (strpos($string, ' and ') === 0) {
            $string = substr($string, 5);
        }
        return $string;
    }
}
