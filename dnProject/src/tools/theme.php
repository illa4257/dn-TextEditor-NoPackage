<?php
namespace tools;

use framework;

class theme 
{

    public static $styles=[
        "day"=>[
            "bg"=>"#f2f2f2",
            "bgEditor"=>"#e6e6e6",
            "colorsEditor"=>[
                "bg"=>"#ccc",
                "bgl"=>"#e3e3e3",
                "tc"=>"#333",
                "tcl"=>"#333",
                "sc"=>"#99f",
                "ec"=>"#000",
                "bgs"=>"#0001",
                "bgsb"=>"#0002",
                "bgsbh"=>"#0003"
            ]
        ],
        "night"=>[
            "file"=>".theme/night.css",
            "bg"=>"#333",
            "bgEditor"=>"#444",
            "colorsEditor"=>[
                "bg"=>"#333",
                "bgl"=>"#444",
                "tc"=>"#f2f2f2",
                "tcl"=>"#f2f2f2",
                "sc"=>"#33b",
                "ec"=>"#fff",
                "bgs"=>"#fff1",
                "bgsb"=>"#fff2",
                "bgsbh"=>"#fff3"
            ]
        ]
    ];

    public static $current="day";
    public static function change($theme){
        if(static::$styles[$theme]==null) $theme="day";
        static::$current=$theme;
        $t=static::$styles[$theme];
        foreach (app()->getForm("MainForm")->tabPane->tabs->toArray() as $tab){
            $tab->data("editor")->settings["colors"]=$t["colorsEditor"];
            $tab->data("editor")->update();
            $tab->data("editor")->layout->backgroundColor=$t["bgEditor"];;
        }
        $forms=[app()->getForm("MainForm"),app()->getForm("Settings"),app()->getForm("about")];
        foreach ($forms as $form){
            $form->clearStylesheets();
            $form->addStylesheet(".theme/style.fx.css");
            if($t["file"]!=null) $form->addStylesheet($t["file"]);
            $form->layout->backgroundColor=$t["bg"];
        }
        app()->getForm("Settings")->items["general"]["style"]["mode"]["value"]=$theme;
    }
    
    public static function get($p){
        return static::$styles[static::$current][$p];
    }

}