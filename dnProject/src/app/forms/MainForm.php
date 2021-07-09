<?php
namespace app\forms;

use std, gui, framework, app;


class MainForm extends AbstractForm
{

    /**
     * @event construct 
     */
    function doConstruct(UXEvent $e = null)
    {    
        $bar = new UXMenuBar;
        $bar->width = $this->layout->width;
        $bar->height = 24;
        $bar->leftAnchor = $bar->rightAnchor = true;
        
        $file = new UXMenu("File");
        $open = new UXMenuItem("Open...");
        $open->on("action",function (){
            $fc = new FileChooserScript;
            if($file=$fc->execute()) $this->newTab($file);
        });
        $file->items->add($open);
        $bar->menus->add($file);
        
        $about = new UXMenu("About");
        $a = new UXMenuItem("About");
        $a->on("action",function (){
            app()->showForm("about");
        });
        $about->items->add($a);
        $bar->menus->add($about);
        
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

    public function newTab(File $file=null){
        $tab = new UXTab($file ? $file->getName() : "new");
        $frag = new UXFragmentPane;
        $editor = new Editor;
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
