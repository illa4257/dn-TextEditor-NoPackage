<?php
namespace app\forms;

use std, gui, framework, app, \tools\lang, \tools\theme;

class MainForm extends AbstractForm
{

    /**
     * @event construct 
     */
    function doConstruct(UXEvent $e = null)
    {
        $cfg=$GLOBALS["cfg"]=new Config; 
        lang::load($l=$cfg->get("lang"));
        theme::change($cfg->get("theme"));
        
        $bar = new UXMenuBar;
        $bar->width = $this->layout->width;
        $bar->height = 24;
        $bar->leftAnchor = $bar->rightAnchor = true;
        
        $file = new UXMenu(lang::get("file"));
        
        $new = new UXMenuItem(lang::get("file.new"));
        $new->on("action",function (){
            $this->newTab();
        });
        
        $open = new UXMenuItem(lang::get("file.open"));
        $open->on("action",function (){
            $fc = new FileChooserScript;
            if($file=$fc->execute()) $this->newTab($file);
        });
        
        $file->items->addAll([$new,$open]);
        
        $adv = new UXMenu(lang::get("adv"));
        
        $settings = new UXMenu(lang::get("settings"));
        $settings->on("action",function (){
            app()->showForm("Settings");
        });
        
        $gen = new UXMenuItem(lang::get("general"));
        
        $style = new UXMenuItem(lang::get("general.style"));
        $style->on("action",function (){
            app()->getForm("Settings")->nav("general.style");
        });
        
        $settings->items->addAll([$gen,$style]);
        
        $a = new UXMenuItem(lang::get("about"));
        $a->on("action",function (){
            app()->showForm("about");
        });
        
        $adv->items->addAll([$settings,$a]);
        $bar->menus->addAll([$file,$adv]);
        
        $this->add($bar);
        
        $this->newTab();
    }

    /**
     * @event tabPane.change 
     */
    function doTabPaneChange(UXEvent $e = null)
    {    
        if($this->tabPane->selectedTab!=null) $this->tabPane->selectedTab->data("editor")->focused=false;
        $e->target->data("editor")->focused=true;
    }

    /**
     * @event tabPane.closeRequest 
     */
    function doTabPaneCloseRequest(UXEvent $e = null)
    {    
        if(!$e->target->data("editor")->saved and !UXDialog::confirm("This file has not been saved! Do you still want to close this tab?")) $e->consume();
        if($this->tabPane->tabs->count()<=1 and !$e->isConsumed()){
            $this->hide();
            exit();
        }
    }

    /**
     * @event close 
     */
    function doClose(UXWindowEvent $e = null)
    {    
        $saved=true;
        foreach ($this->tabPane->tabs->toArray() as $tab) if(!$tab->data("editor")->saved) $saved=false;
        if(!$saved and !UXDialog::confirm("You have unsaved files!")) $e->consume();
    }

    public function newTab(File $file=null){
        $tab = new UXTab($file ? $file->getName() : "new");
        $tab->draggable=true;
        $frag = new UXFragmentPane;
        $editor = new Editor;
        $editor->layout->backgroundColor=theme::get("bgEditor");
        $editor->settings["colors"]=theme::get("colorsEditor");
        $editor->observer("title")->addListener(function ($old,$new) use ($tab){
            $tab->text=$new;
        });
        $editor->file = $file;
        if($file) $editor->text = file_get_contents(fs::abs($file));
        $editor->showInFragment($frag);
        $tab->data("editor",$editor);
        $tab->content = $frag;
        $this->tabPane->tabs->add($tab);
        $this->observer("width")->addListener(function($old,$new) use ($tab){
            $tab->content->width += $new-$old;
        });
        $this->observer("height")->addListener(function($old,$new) use ($tab){
            $tab->content->height += $new-$old;
        });
        $this->tabPane->selectedTab=$tab;
    }
}

class Config {
    public $dir;
    
    public function __construct(){
        $this->dir = System::getProperty("jphp.trace")=="true" ? fs::abs("./") : fs::parent($GLOBALS["argv"][0]);
        $this->dir.="/config.ini";
    }
    
    public function keys(){
        $l = [];
        if(($data=file_get_contents($this->dir))!==false){
            foreach (str::lines($data) as $line) $l[str::sub($line,0,str::pos($line,"="))]=str::sub($line,str::pos($line,"=")+1);
        }else false;
        return $l;
    }
    
    public function get($key){
        if($arr=$this->keys()) return $arr[$key];
    }
    
    public function set($key,$value){
        if($keys=$this->keys()){
            $keys[$key]=$value;
            $data="";
            foreach ($keys as $key=>$value){
                $data.=$key."=".$value."\n";
            }
            return file_put_contents($this->dir,$data)!==false;
        }else return false;
    }
}