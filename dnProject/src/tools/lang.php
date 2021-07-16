<?php
namespace tools;

use std;
use gui;
use framework;

class lang 
{

    public static $words = [];

    public static function get($key){
        return static::$words[$key] ? static::$words[$key] : $key;
    }
    
    public static function translate($obj){
        switch(get_class($obj)){
            case 'php\\gui\\UXTreeItem':
                $obj->value=static::get($obj->value);
                foreach ($obj->children->toArray() as $i) static::translate($i);
                break;
            default:
                Logger::info(get_class($obj));
                break;
        }
    }
    
    public static $current;
    
    public static function load($lang=""){
        $rl=Locale::ENGLISH()->getLanguage();
        $l = [
            "app.restartTAC"=>"Restart the program to apply the changes.",
            "general"=>"General",
            "general.lang"=>"Language:",
            "general.en"=>"English",
            "general.ru"=>"Русский",
            "general.style.theme"=>"Theme:",
            "general.style"=>"Style",
            "general.style.night"=>"Night",
            "general.style.day"=>"Day",
            "file"=>"File",
            "file.new"=>"New file",
            "file.open"=>"Open...",
            "file.noWritable"=>"The configuration file is not writable.",
            "adv"=>"Advanced",
            "settings"=>"Settings",
            "about"=>"About"
        ];
        switch($lang){
            case Locale::RUSSIAN()->getLanguage():
                $l = [
                    "app.restartTAC"=>"Перезапустите программу, чтобы изменения вступили в силу.",
                    "general"=>"Общий",
                    "general.style.theme"=>"Тема:",
                    "general.style"=>"Стиль",
                    "general.style.night"=>"Ночной",
                    "general.style.day"=>"Дневной",
                    "file"=>"Файл",
                    "file.new"=>"Новый файл",
                    "file.open"=>"Открыть...",
                    "file.noWritable"=>"Файл конфигурации недоступен для записи.",
                    "adv"=>"Расширенные",
                    "settings"=>"Настройки",
                    "about"=>"О"
                ]+$l;
                $rl=Locale::RUSSIAN()->getLanguage();
            default:
                static::$words=$l;
                static::$current=$rl;
                break;
        }
    }

}