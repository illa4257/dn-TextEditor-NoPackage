<?php
namespace app\forms;

use std, gui, framework, app, \tools\lang, \tools\lang;


class Settings extends AbstractForm
{

    public $items = [
        "general"=>[
            "name"=>"general",
            "langTitle"=>["type"=>"title","name"=>"lang"],
            "lang"=>["type"=>"combobox","value"=>"","items"=>[],"onAction"=>"langChanged"],
            "style"=>[
                "name"=>"general.style",
                "themeTitle"=>["type"=>"title","name"=>"theme"],
                "mode"=>["type"=>"combobox","value"=>"day","items"=>["day","night"],"onAction"=>"themeChanged"]
            ]
        ]
    ];
    
    public function getItem($id,$items=null){
        if($items==null) $items=$this->items;
        foreach ($items as $key=>$value){
            if(gettype($value)=="array"){
                if($value["name"]==$id) return $value;
                elseif($value["type"]==null){
                    if($obj=$this->getItem($id,$value))
                        return $obj;
                }
            }
        }
        return null;
    }
    
    public function getTreeItem($id, UXTreeItem $item=null){
        if($item==null) $item=$this->tree->root;
        foreach ($item->children->toArray() as $i){
            if($this->links[$i->value]==$id){
                return $i;
            }elseif($obj=$this->getTreeItem($id,$i)) return $obj;
        }
        return null;
    }
    
    public $links = [];

    /**
     * @event show 
     */
    function doShow(UXWindowEvent $e = null){
        if($this->tree->root==null){
            $root = new UXTreeItem;
            $this->makeTree($root,$this->items);
            $this->tree->root=$root;
            $this->items["general"]["lang"]["value"]=lang::$current;
            $l=[Locale::ENGLISH()->getLanguage(),Locale::RUSSIAN()->getLanguage()];
            foreach ($l as $lang) $this->items["general"]["lang"]["items"][]=$lang;
        }
        $this->nav("general");
        $this->tree->focusedItem=$this->tree->root->children->offsetGet(0);
    }

    /**
     * @event tree.click 
     */
    function doTreeClick(UXMouseEvent $e = null){
        $this->nav($this->links[$this->tree->focusedItem ? $this->tree->focusedItem->value : $this->tree->root->children->offsetGet(0)],false);
    }
    
    public function makeTree(UXTreeItem $tree,array $arr){
        foreach ($arr as $name=>$value){
            if($value["type"]==null and $name!="name"){
                $child=new UXTreeItem(lang::get($value["name"]));
                $this->links[$child->value]=$value["name"];
                $this->makeTree($child,$value);
                $tree->children->add($child);
            }
        }
    }

    public function nav($n,$f=true){
        $vbox = new UXVBox;
        $vbox->padding=[5,5,5,5];
        $arr=$this->items;
        foreach (explode(".",$n) as $child) $arr=$arr[$child];
        if($f) $this->tree->selectedItems=[$this->getTreeItem($n)];
        foreach ($arr as $name=>$value){
            if($value["type"]==null){
                $link=new UXHyperlink(lang::get($value["name"]));
                $link->on("action",function () use ($name,$n){
                    $this->nav($n.".".$name);
                });
                $vbox->add($link);
            }elseif($value["type"]=="combobox"){
                $box = new UXComboBox;
                $this->links[$box->value=lang::get($arr["name"].".".$value["value"])]=$value["name"].$value["value"];
                foreach ($value["items"] as $item){
                    $box->items->add(lang::get($arr["name"].".".$item));
                    $this->links[lang::get($arr["name"].".".$item)]=$item;
                }
                $nf=$value["onAction"];
                if($nf!=null)
                    $box->on("action",function () use ($box,$nf){
                        $this->$nf($box);
                    });
                $vbox->add($box);
            }elseif($value["type"]=="title"){
                $box = new UXLabelEx;
                $box->text=lang::get($arr["name"].".".$value["name"]);
                $vbox->add($box);
            }
        }
        $this->options->content=$vbox;
    }
    
    public function langChanged(UXComboBox $cb){
        $l=$this->links[$cb->value];
        lang::load($l);
        $this->items["general"]["lang"]["value"]=$l;
        if(!$GLOBALS["cfg"]->set("lang",$l)) alert(lang::get("file.noWritable")); else alert(lang::get("app.restartTAC"));
    }
    
    public function themeChanged(UXComboBox $cb){
        $t=$this->links[$cb->value];
        theme::change($t);
        if(!$GLOBALS["cfg"]->set("theme",$t)) alert(lang::get("file.noWritable"));
    }

}
