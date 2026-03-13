<?php namespace Manage\Api;
use Dever;
use Manage\Lib\Auth;
class Console extends Auth
{
    # 控制台首页
    public function index()
    {
        $config = Dever::config('manage');
        $data['layout'] = [
            [
                'tip' => 24,
            ],
        ];
        $data['tip'] = [
            'type' => 'tip',
            'name' => $this->getMsg($this->user['name']),
            'content' => '您正在使用{title}，请通过左侧导航栏选择需要操作的模块。祝你工作愉快！',
        ];
        if (isset($config['console']) && $config['console']) {
            $data = Dever::call($config['console'], [$data]);
        }
        return $data;
    }

    # 问候语
    private function getMsg($username, $lang = 'zh')
    {
        $hour = date('H');
        if ($hour < 8) {
            $period = 'morning_early';
        } elseif ($hour <= 11) {
            $period = 'morning';
        } elseif ($hour <= 13) {
            $period = 'noon';
        } elseif ($hour < 18) {
            $period = 'afternoon';
        } else {
            $period = 'evening';
        }

        $greetings = [
            'zh' => [
                'morning_early' => [
                    "新的一天开始啦，{username}，愿你充满能量与好心情 🌅",
                    "早上好，{username}！今天也要元气满满地出发 ☀️",
                    "又是充满希望的一天，{username}，愿你元气满满 ✨",
                    "清晨的阳光最温柔，祝你一天好心情，{username} 🌞",
                    "清晨的努力，是成功的开始，加油，{username}！",
                    "起这么早，是被梦想叫醒的，还是被闹钟吓醒的？⏰，{username}",
                    "早安，{username}，太阳都羡慕你勤劳 🌞",
                    "日出东方，一切皆静，{username} 🌄",
                    "时间不语，却回答一切，{username} 🧘",
                    "越努力，越幸运，新的一天，加油，{username} 💪",
                    "今天做的每一件小事，都是未来的伏笔，{username} 📝",
                    "美好的一天从早晨开始，{username}，加油！",
                    "黎明的第一缕光，送给勤奋的你，{username}。",
                ],
                'morning' => [
                    "上午好，{username}！看到你上线真开心 😄",
                    "今天也要高效完成每一项任务，加油，{username} 💪",
                    "专注的你，最有魅力，{username} 🧠",
                    "把事情做到最好，是你的风格，{username} 🔧",
                    "保持节奏，每一天都值得记录，{username} 📅",
                    "上午好，{username}！今天也要继续摸鱼计划 🐟",
                    "再摸一会鱼，就到中午了，加油，{username} ✊",
                    "你走你的路，花自开，{username} 🌸",
                    "心静则清，行稳则远，{username} 🌿",
                    "你正在书写属于你自己的不凡人生，{username} 📖",
                    "相信自己，你比想象中更强大，{username} ✨",
                    "每个上午都是新的开始，{username}，抓住机会！",
                    "专注且坚定，{username}，你值得赞扬！",
                ],
                'noon' => [
                    "中午好，{username}！记得按时吃饭补充能量 🍱",
                    "工作再忙，也别忘了照顾自己，{username} ❤️",
                    "好好吃饭，下午才有力气继续冲，{username} 💼",
                    "中场休息，补充体力，{username} 🌯",
                    "饭不吃饱，哪有力气摸鱼，{username} 🐠",
                    "中午不休息，下午徒伤悲，{username} 😵",
                    "饭要好好吃，觉要好好睡，{username} 🌿",
                    "坐看云起时，不争一时高下，{username} ☁️",
                    "每一次坚持，都是积累能量，{username} 🔋",
                    "中午短暂放松，是为了更好出发，{username} 🚀",
                    "阳光正好，{username}，午饭别忘了吃饱哦！",
                    "充能中，{username}，下午继续加油！",
                ],
                'afternoon' => [
                    "下午好，{username}！来杯咖啡提提神 ☕",
                    "再坚持一会儿，胜利就在眼前，{username} 🏁",
                    "保持专注，继续向前，{username} 💼",
                    "喝口水，伸个懒腰，继续冲，{username} 💨",
                    "摸鱼也需要节奏，别太张扬，{username} 😏",
                    "困了就看老板照片提神，{username} 🧃",
                    "一花一世界，一念一清净，{username} 🪷",
                    "茶要慢饮，事要缓做，{username} 🍵",
                    "你所付出的努力，终将照亮前路，{username} 💡",
                    "不怕慢，只怕站，坚持走就对了，{username} 🛤️",
                    "努力的下午，{username}，胜利不远了！",
                    "冲刺时间到，{username}，继续燃烧吧！",
                ],
                'evening' => [
                    "晚上好，{username}！愿你天黑有灯，下雨有伞 🌙",
                    "今天也辛苦啦，好好休息，{username} 🛏️",
                    "收工啦，放松一下，明天继续努力，{username} ✨",
                    "一天结束了，给自己点个赞，{username} 👍",
                    "打卡下班是对生活最基本的尊重，{username} 📤",
                    "今天摸鱼圆满成功，记得下次继续，{username} 🐳",
                    "夜深人静时，心要平，{username} 💭",
                    "万事随心，内心清明，{username} ✨",
                    "收获不在今天，也会在明天到来，{username} 🌟",
                    "夜晚是沉淀的时刻，也是蓄力的开始，{username} 🌌",
                    "星空很美，{username}，别忘了好好休息！",
                    "忙碌一天，{username}，放松自己，明天更好！",
                ],
            ],
            'en' => [
                'morning_early' => [
                    "Good morning {username}! A new day, a new beginning ☀️",
                    "Wake up, {username}! The sun is shining just for you 🌞",
                    "Early bird {username}, you’re catching all the worms! 🐦",
                    "Rise and shine, {username}! Let's seize the day! ✨",
                    "Morning, {username}! The world awaits your greatness!",
                ],
                'morning' => [
                    "Good morning {username}! Let's make today productive 💪",
                    "Hey {username}, rise and grind! ☕",
                    "Keep pushing forward, {username}. Success awaits! 🚀",
                    "Stay focused, {username}, and make it happen! 🧠",
                    "Morning hustle, {username}! Keep that energy high!",
                    "Seize the morning, {username}! Make it count!",
                ],
                'noon' => [
                    "Hi {username}, don't forget to grab some lunch 🍱",
                    "Take a break, {username}, recharge your energy! ⚡",
                    "Refuel well, {username}, the afternoon awaits! 🌟",
                    "Lunch time, {username}! Enjoy your meal 🍔",
                    "Midday break, {username}! Stay refreshed!",
                    "Keep up the great work, {username}! Almost halfway!",
                ],
                'afternoon' => [
                    "Afternoon vibes, {username}! Keep going strong 💼",
                    "Almost there, {username}! Keep up the great work 🏆",
                    "Stay hydrated, {username}, and keep focused! 💧",
                    "You’re doing great, {username}! Keep pushing! 🔥",
                    "Keep the momentum, {username}! Afternoon grind!",
                    "Push through, {username}! The finish line is near!",
                ],
                'evening' => [
                    "Good evening {username}! Time to wind down 🌙",
                    "Relax and recharge, {username}, you earned it 🛏️",
                    "Well done today, {username}! See you tomorrow 👋",
                    "Evenings are for rest, {username}. Take care! 🌌",
                    "Night falls, {username}. Rest well and dream big!",
                    "Time to relax, {username}. Tomorrow is a new chance!",
                ],
            ],
        ];

        $langGroup = $greetings[$lang] ?? $greetings['zh'];
        $lines = $langGroup[$period] ?? $langGroup['morning_early'];

        $template = $lines[array_rand($lines)];

        return str_replace('{username}', $username, $template);
    }
}