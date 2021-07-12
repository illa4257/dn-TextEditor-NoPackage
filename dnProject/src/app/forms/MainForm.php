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
        $frag = new UXFragmentPane;
        $editor = new Editor;
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
