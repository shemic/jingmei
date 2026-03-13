from __future__ import annotations

import json
import importlib
from pathlib import Path

class Dever(object):
    config = {}
    _instance_cache = {}

    @classmethod
    def initConfig(self):
        if self.config == {}:
            filename = Path(__file__).resolve().parents[3] / "config" / "setting.json"
            self.config = json.loads(filename.read_text(encoding="utf-8"))
        return self.config
    
    @classmethod
    def readConfig(self, name):
        return self.initConfig().get(name)
    
    @classmethod
    def load(self, name, cache = 'main', base = 'tools', *args, **kwargs):
        if cache is None or cache is False or cache == "":
            base = base.rstrip('.')
            path = base + '.'
            klass = self.getClass(name, path)
            return klass(*args, **kwargs)
        cached = self._instance_cache.get(cache)
        if cached is not None:
            return cached
        base = base.rstrip('.')
        path = base + '.'
        klass = self.getClass(name, path)
        inst = klass(*args, **kwargs)
        self._instance_cache[cache] = inst
        return inst
    
    @staticmethod
    def getObject(name, path = ''):
        return importlib.import_module(path + name)

    @classmethod
    def getClass(self, name, path=''):
        obj = self.getObject(name, path)
        last = name.split('.')[-1]
        class_name = last[:1].upper() + last[1:]
        return getattr(obj, class_name)
