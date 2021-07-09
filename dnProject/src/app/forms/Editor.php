<?php
namespace app\forms;

use std, gui, framework, app;


class Editor extends AbstractForm
{

    public $text="Hello, world!\nTest TextEditor!\nLine1\nLine2\nLine3\nLine4\nLine5\nLine6\nLine7\nLine8\nLine9";
    
    /**
     * @var File
     */
    public $file;
    
    public $focused = false;
    
    /**
     * @var UXGraphicsContext
     */
    private $gc;
    
    private $skip = false;
    private $update = true;
    
    private $selection = ["x"=>0,"s"=>false,"sx"=>0,"l"=>0];
    
    private function getLine($y=-1){
        if($y==-1) $y = $this->getIndexLine();
        return explode("\n",$this->text)[$y];
    }
    
    private function getIndexLine($x=-1){
        if($x==-1) $x = $this->selection["x"];
        return max(arr::count(explode("\n",str::sub($this->text,0,$x)))-1,0);
    }

    /**
     * @event canvas.step 
     */
    function doCanvasStep(UXEvent $e = null)
    {    
        if(!$this->combobox->focused and $this->focused) $this->canvas->requestFocus();
        if(!$this->update or !$this->focused) return;
        $this->update=false;
        if($this->gc==null){
            $this->gc = $this->canvas->gc();
            $this->combobox->value = $this->gc->font->family;
            foreach (UXFont::getFamilies() as $f) $this->combobox->items->add($f);
            $this->update=true;
            $this->canvas->observer("width")->addListener(function (){$this->update=true;});
            $this->canvas->observer("height")->addListener(function (){$this->update=true;});
        }
        $w = $this->canvas->width;
        $h = $this->canvas->height;
        $gc = $this->gc;
        $cols = [
            "bg"=>"#333",
            "bgl"=>"#444",
            "tc"=>"#f2f2f2",
            "tcl"=>"#f2f2f2",
            "sc"=>"#33b",
            "ec"=>"#fff"
        ];
        $to = 3;
        $tlo = 5;
        $bglo = 8;
        $bglw = $gc->font->calculateTextWidth((string)max(arr::count(explode("\n",$this->text))-1,0))+$bglo*2;
        $gc->fillColor = $cols["bgl"];
        $gc->fillRect(0,0,$bglw,$h);
        $gc->fillColor = $cols["bg"];
        $gc->fillRect($bglw,0,$w-$bglw,$h);
        $l = -1;
        $b = false;
        foreach (explode("\n",$this->text) as $y=>$line){
            $ol=$l+1;
            $l+=str::length($line)+1;
            $gc->fillColor=$cols["tcl"];
            $ly=$y*($gc->font->size+$to)+$to;
            $ty=$ly+$gc->font->size;
            $gc->fillText($y,$bglw-($gc->font->calculateTextWidth($y)+$bglo),$ty);
            if($this->selection["s"]){
                $x=$bglw+$tlo;
                $w=$gc->font->calculateTextWidth($line);
                if($l>=$this->selection["sx"] and $this->selection["sx"]>=$ol){
                    $b=true;
                    $x+=$gc->font->calculateTextWidth(str::sub($line,0,$this->selection["sx"]-$ol));
                    $w-=$gc->font->calculateTextWidth(str::sub($line,0,$this->selection["sx"]-$ol));
                }
                $r=$b;
                if($l>=$this->selection["sx"]+$this->selection["l"]){
                    $b=false;
                    $w-=$gc->font->calculateTextWidth(str::sub($line,$this->selection["sx"]+$this->selection["l"]-$ol));
                }
                if($r){
                    $gc->fillColor = $cols["sc"];
                    $gc->fillRect($x,$ly,$w,$gc->font->size);
                }
            }
            if($ol<=$this->selection["x"] and $l>=$this->selection["x"]){
                $gc->fillColor = $cols["ec"];
                $gc->fillRect($bglw+$tlo+$gc->font->calculateTextWidth(str::sub($line,0,$this->selection["x"]-$ol)),$ly,2,$gc->font->size);
            }
            $gc->fillColor=$cols["tc"];
            $gc->fillText($line,$bglw+$tlo,$ty);
        }
    }

    /**
     * @event combobox.action 
     */
    function doComboboxAction(UXEvent $e = null)
    {    
        $this->gc->font=UXFont::of($this->combobox->value,$this->gc->font->size);
    }

    /**
     * @event canvas.keyDown 
     */
    function doCanvasKeyDown(UXKeyEvent $e = null)
    {    
        $lt = str::length($this->text);
        if($e->isArrowKey()){
            $this->update=true;
            switch($e->codeName){
                case "Left":
                    if($this->selection["x"]>0){
                        if($e->shiftDown){
                            if(!$this->selection["s"]){
                                $this->selection["sx"]=$this->selection["x"];
                                $this->selection["l"]=0;
                            }
                            if($this->selection["sx"]==$this->selection["x"]){
                                $this->selection["sx"]--;
                                $this->selection["l"]++;
                            }elseif($this->selection["sx"]+$this->selection["l"]==$this->selection["x"]) $this->selection["l"]--;
                            $this->selection["s"]=true;
                        }else $this->selection["s"]=false;
                        $this->selection["x"]--;
                    }
                    break;
                case "Up":
                    if($this->selection["x"]>0){
                        if($e->shiftDown){
                            if(!$this->selection["s"]){
                                $this->selection["sx"]=$this->selection["x"];
                                $this->selection["l"]=0;
                            }
                            if($this->selection["sx"]==$this->selection["x"]){
                                $step=str::length($this->getLine($this->getIndexLine($this->selection["x"]-str::length($this->getLine()))))+1;
                                $this->selection["sx"]-=$step;
                                $this->selection["l"]+=$step;
                                if($this->selection["sx"]<0){
                                    $this->selection["l"]+=$this->selection["sx"];
                                    $this->selection["sx"]=0;
                                }
                            }elseif($this->selection["sx"]+$this->selection["l"]==$this->selection["x"]){
                                $this->selection["l"]-=str::length($this->getLine($this->getIndexLine($this->selection["x"]-str::length($this->getLine()))))+1;
                                if($this->selection["l"]<0){
                                    $this->selection["sx"]+=$this->selection["l"];
                                    $this->selection["l"]=-$this->selection["l"];
                                }
                            }
                            $this->selection["s"]=$this->selection["l"]!=0;
                        }else $this->selection["s"]=false;
                        $this->selection["x"]-=str::length($this->getLine($this->getIndexLine($this->selection["x"]-str::length($this->getLine()))))+1;
                        $this->selection["x"]=max($this->selection["x"],0);
                    }
                    break;
                case "Right":
                    if($lt>$this->selection["x"]){
                        if($e->shiftDown){
                            if(!$this->selection["s"]){
                                $this->selection["sx"]=$this->selection["x"];
                                $this->selection["l"]=0;
                            }
                            if($this->selection["sx"]+$this->selection["l"]==$this->selection["x"])
                                $this->selection["l"]++;
                            elseif($this->selection["sx"]==$this->selection["x"]){
                                $this->selection["sx"]++;
                                $this->selection["l"]--;
                            }
                            $this->selection["s"]=$this->selection["l"]!=0;
                        }else $this->selection["s"]=false;
                        $this->selection["x"]++;
                    }
                    break;
                case "Down":
                    if($lt>$this->selection["x"]){
                        if($e->shiftDown){
                            if(!$this->selection["s"]){
                                $this->selection["sx"]=$this->selection["x"];
                                $this->selection["l"]=0;
                            }
                            if($this->selection["sx"]+$this->selection["l"]==$this->selection["x"]){
                                $this->selection["l"]+=str::length($this->getLine())+1;
                                $this->selection["l"]=min($this->selection["l"],$lt);
                            }elseif($this->selection["sx"]==$this->selection["x"]){
                                $this->selection["sx"]+=str::length($this->getLine())+1;
                                $this->selection["l"]-=str::length($this->getLine())+1;
                                if($this->selection["l"]<0){
                                    $this->selection["sx"]+=$this->selection["l"];
                                    $this->selection["l"]=-$this->selection["l"];
                                }
                            }
                            $this->selection["s"]=$this->selection["l"]!=0;
                        }else $this->selection["s"]=false;
                        $this->selection["x"]+=str::length($this->getLine())+1;
                        $this->selection["x"] = min($this->selection["x"],$lt);
                    }
                    break;
            }
        }elseif($e->codeName=="Backspace"){
            $this->skip=true;
            $this->text=$this->selection["s"] ? str::sub($this->text,0,$this->selection["sx"]).str::sub($this->text,$this->selection["sx"]+$this->selection["l"]) : str::sub($this->text,0,$this->selection["x"]-1).str::sub($this->text,$this->selection["x"]);
            if($this->selection["s"]){
                $this->selection["s"]=false;
                if($this->selection["x"]!=$this->selection["sx"]){
                    $this->selection["x"]-=$this->selection["l"];
                    if($this->selection["x"]<0) $this->selection["x"]=0;
                }
            }else $this->selection["x"]-=$this->selection["x"]>0 ? 1 : 0;
            $this->update=true;
        }elseif($e->codeName=="Delete"){
            $this->skip = true;
            $this->text=$this->selection["s"] ? str::sub($this->text,0,$this->selection["sx"]).str::sub($this->text,$this->selection["sx"]+$this->selection["l"]) : str::sub($this->text,0,$this->selection["x"]).str::sub($this->text,$this->selection["x"]+1);
            if($this->selection["s"]){
                $this->selection["s"]=false;
                if($this->selection["x"]!=$this->selection["sx"]){
                    $this->selection["x"]-=$this->selection["l"];
                    if($this->selection["x"]<0) $this->selection["x"]=0;
                }
            }
            $this->update=true;
        }elseif($e->codeName=="Esc")
            $this->skip=true;
        elseif($e->controlDown){
            if($e->codeName=="C"){
                if($this->selection["s"]){
                    UXClipboard::setText(str::sub($this->text,$this->selection["sx"],$this->selection["sx"]+$this->selection["l"]));
                }
                $this->update=true;
            }elseif($e->codeName=="V"){
                $cb = implode("\n",str::lines(UXClipboard::getText()));
                $this->text=$this->selection["s"] ? str::sub($this->text,0,$this->selection["sx"]).$cb.str::sub($this->text,$this->selection["sx"]+$this->selection["l"]) : str::sub($this->text,0,$this->selection["x"]).$cb.str::sub($this->text,$this->selection["x"]);
                if($this->selection["s"]){
                    if($this->selection["sx"]!=$this->selection["x"]) $this->selection["x"]-=$this->selection["l"];
                    $this->selection["s"]=false;
                }
                $this->selection["x"]+=str::length($cb);
                $this->update=true;
            }elseif($e->codeName=="A"){
                $this->selection["s"]=true;
                $this->selection["sx"]=0;
                $this->selection["l"]=str::length($this->text);
                $this->update=true;
            }elseif($e->codeName=="S"){
                if($this->file==null){
                    $fc = new FileChooserScript;
                    $fc->saveDialog=true;
                    $fc->initialFileName="new.txt";
                    if($file=$fc->execute()) $this->file = $file;
                }
                if($this->file!=null){
                    file_put_contents(fs::abs($this->file),$this->text);
                    alert("Файл ".fs::name($this->file)." cохранён!");
                }
            }
            $this->skip=true;
        }
    }

    /**
     * @event canvas.keyPress 
     */
    function doCanvasKeyPress(UXKeyEvent $e = null)
    {    
        if($this->skip){ $this->skip=false; return; }
        $chr = $e->character;
        if(str::lines($chr)[0]=="") $chr = "\n";
        $this->text = $this->selection["s"] ? str::sub($this->text,0,$this->selection["sx"]).$chr.str::sub($this->text,$this->selection["sx"]+$this->selection["l"]) : str::sub($this->text,0,$this->selection["x"]).$chr.str::sub($this->text,$this->selection["x"]);
        if($this->selection["s"]){
            $this->selection["s"] = false;
            if($this->selection["x"]!=$this->selection["sx"]) $this->selection["x"]-=$this->selection["l"];
            $this->selection["x"]+=str::length($chr);
        }else $this->selection["x"]+=str::length($chr);
        if(str::length($chr)>0) $this->update=true;
    }

}
