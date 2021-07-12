<?php
namespace app\forms;

use std, gui, framework, app;


class Editor extends AbstractForm
{

    public $text="";
    
    /**
     * @var File
     */
    public $file;
    public $focused = false;
    public $saved = true;
    
    /**
     * @var UXGraphicsContext
     */
    private $gc;
    private $skip = false;
    private $update = true;
    private $selection = ["x"=>0,"o"=>0,"s"=>false,"sx"=>0,"l"=>0];
    private $scrollX = 0;
    private $maxScrollX = 0;
    private $scrollY = 0;
    private $maxScrollY = 0;
    private $cx = -1;
    private $cy = -1;
    private $xmode = false;
    private $settings = [
        "colors"=>[
            "bg"=>"#333",
            "bgl"=>"#444",
            "tc"=>"#f2f2f2",
            "tcl"=>"#f2f2f2",
            "sc"=>"#33b",
            "ec"=>"#fff",
            "bgs"=>"#fff1",
            "bgsb"=>"#fff2",
            "bgsbh"=>"#fff3"
        ],
        "textOffset"=>3,
        "bgLineOffset"=>8,
        "lineOffset"=>5
    ];
    
    private function getLine($y=-1){
        if($y==-1) $y = $this->getIndexLine();
        return explode("\n",$this->text)[$y];
    }
    
    private function getIndexLine($x=-1){
        if($x==-1) $x = $this->selection["x"];
        return max(arr::count(explode("\n",str::sub($this->text,0,$x)))-1,0);
    }
    
    private function getBGLW(){ return $this->gc->font->calculateTextWidth((string)max(arr::count(explode("\n",$this->text))-1,0))+$this->settings["bgLineOffset"]*2; }
    
    private function editedFile(){
        $this->title="*".($this->file ? $this->file->getName() : "new");
        $this->saved=false;
    }
    
    /**
     * @return bool
     */
    public function save(){
        if($this->file==null){
            $fc = new FileChooserScript;
            $fc->saveDialog=true;
            $fc->initialFileName="new.txt";
            if($file=$fc->execute()) $this->file = $file;
        }
        if($this->file and file_put_contents(fs::abs($this->file),$this->text)!==false){
            $this->title=$this->file->getName();
            return $this->saved=true;
        }
        return false;
    }

    /**
     * @event canvas.step 
     */
    function doCanvasStep(UXEvent $e = null){
        if(!$this->combobox->focused and $this->focused) $this->canvas->requestFocus();
        if(!$this->update or !$this->focused) return;
        $this->update=false;
        if($this->gc==null){
            $this->gc = $this->canvas->gc();
            $this->combobox->value = $this->gc->font->family;
            foreach (UXFont::getFamilies() as $f) $this->combobox->items->add($f);
            $this->fontSize->value=$this->gc->font->size;
            $this->update=true;
            $this->canvas->observer("width")->addListener(function (){$this->update=true;});
            $this->canvas->observer("height")->addListener(function (){$this->update=true;});
            $this->fontSize->observer("value")->addListener(function($old,$new){
                $this->gc->font=$this->gc->font->withSize($new);
                $this->update=true;
            });
        }
        $cur = "TEXT";
        $w = $this->canvas->width;
        $h = $this->canvas->height;
        $gc = $this->gc;
        $cols = $this->settings["colors"];
        $to = $this->settings["textOffset"];
        $tlo = $this->settings["lineOffset"];
        $bglo = $this->settings["bgLineOffset"];
        $wo = 32;
        $bglw=$this->getBGLW();
        $gc->fillColor = $cols["bg"];
        $gc->fillRect($bglw-1,0,$w-$bglw+1,$h);
        $l = -1;
        $b = false;
        $lines=explode("\n",$this->text);
        $this->maxScrollX=max($gc->font->calculateTextWidth($this->text)-$w+$wo,0);
        $this->maxScrollY=max((arr::count($lines)+1)*($gc->font->size+$to)+$to*2-$h,0);
        if($this->scrollX>$this->maxScrollX) $this->scrollX=$this->maxScrollX;
        if($this->scrollY>$this->maxScrollY) $this->scrollY=$this->maxScrollY;
        if($this->scrollX<0) $this->scrollX=0;
        if($this->scrollY<0) $this->scrollY=0;
        $gc->fillColor = $cols["bgl"];
        $gc->fillRect(0,0,$bglw,$h);
        foreach ($lines as $y=>$line){
            $ol=$l+1;
            $l+=str::length($line)+1;
            $ly=$y*($gc->font->size+$to)+$to-$this->scrollY;
            $ty=$ly+$gc->font->size;
            $render=($ty+$gc->font->size*2>0 and $ly<$h);
            if($this->selection["s"]){
                $x=$bglw+$tlo-$this->scrollX;
                $ws=$gc->font->calculateTextWidth($line);
                if($l>=$this->selection["sx"] and $this->selection["sx"]>=$ol){
                    $b=true;
                    if($render){
                        $x+=$gc->font->calculateTextWidth(str::sub($line,0,$this->selection["sx"]-$ol));
                        $ws-=$gc->font->calculateTextWidth(str::sub($line,0,$this->selection["sx"]-$ol));
                    }
                }
                $r=$b;
                if($l>=$this->selection["sx"]+$this->selection["l"]){
                    $b=false;
                    if($render) $ws-=$gc->font->calculateTextWidth(str::sub($line,$this->selection["sx"]+$this->selection["l"]-$ol));
                }
                if($r and $render){
                    $gc->fillColor = $cols["sc"];
                    $gc->fillRect($x,$ly,$ws,$gc->font->size+$to);
                }
            }
            if($ol<=$this->selection["x"] and $l>=$this->selection["x"] and $render){
                $gc->fillColor = $cols["ec"];
                $gc->fillRect($bglw+$tlo-$this->scrollX+$gc->font->calculateTextWidth(str::sub($line,0,$this->selection["x"]-$ol)),$ly,2,$gc->font->size+$to);
            }
            if($render){
                $gc->fillColor=$cols["tc"];
                $gc->fillText($line,$bglw+$tlo-$this->scrollX,$ty);
                $gc->fillColor = $cols["bgl"];
                $gc->fillRect(0,$ly,$bglw,$gc->font->size+$to);
                $gc->fillColor=$cols["tcl"];
                $gc->fillText($y,$bglw-($gc->font->calculateTextWidth($y)+$bglo),$ty);
            }
        }
        $gc->fillColor=$cols["bgs"];
        $gc->fillRect($w-16,0,16,$h-16);
        $vs=max((1-min(1/($h-16)*max($this->maxScrollY,1),1))*($h-16),16);
        $vsy=(1/$this->maxScrollY)*$this->scrollY*($h-16-$vs);
        if($this->cx>$w-16 and $this->cy<$h-16) $cur="DEFAULT";
        $gc->fillColor=($this->cx>$w-16 and $vsy+$vs>$this->cy and $this->cy>$vsy) ? $cols["bgsbh"] : $cols["bgsb"];
        $gc->fillRect($w-16,$vsy,16,$vs);
        $gc->fillColor=$cols["bgs"];
        $gc->fillRect(0,$h-16,$w-16,16);
        $hs=max((1-min(1/($w-16)*max($this->maxScrollX,1),1))*($w-16),16);
        $hsx=(1/$this->maxScrollX)*$this->scrollX*($w-16-$hs);
        $this->xmode=($this->cy>$h-16 and $this->cx<$w-16);
        if($this->xmode) $cur="DEFAULT";
        $gc->fillColor=($this->cy>$h-16 and $hsx+$hs>$this->cx and $this->cx>$hsx) ? $cols["bgsbh"] : $cols["bgsb"];
        $gc->fillRect($hsx,$h-16,$hs,$hs);
        if($this->cx<$bglw) $cur="DEFAULT";
        $this->canvas->cursor=$cur;
    }

    /**
     * @event combobox.action 
     */
    function doComboboxAction(UXEvent $e = null){ $this->gc->font=UXFont::of($this->combobox->value,$this->gc->font->size); }
    
    public function scroll(){
        $lw=$this->gc->font->calculateTextWidth((string)max(arr::count(explode("\n",$this->text))-1,0))+$this->settings["lineOffset"]*2+$this->settings["textOffset"];
        $w=$this->canvas->width;
        $tw=$this->gc->font->calculateTextWidth(str::sub($this->getLine(),0,$this->selection["x"]-1-str::lastPos(str::sub($this->text,0,$this->selection["x"]),"\n")))+$lw*2;
        if($this->scrollX+$w<$tw) $this->scrollX=$tw-$w; $tw-=$lw*2;
        if($this->scrollX>$tw) $this->scrollX=$tw;
        $h=$this->canvas->height;
        $y=$this->getIndexLine($this->selection["x"])*($this->gc->font->size+$this->settings["textOffset"])+$this->gc->font->size+$this->settings["textOffset"];
        if($y>$this->scrollY+$h-$this->gc->font->size) $this->scrollY=$y+$this->gc->font->size+$this->settings["textOffset"]*3-$h;
        if($this->scrollY+$this->gc->font->size>$y) $this->scrollY=$y-$this->gc->font->size;
    }

    /**
     * @event canvas.keyDown 
     */
    function doCanvasKeyDown(UXKeyEvent $e = null){
        $lt = str::length($this->text);
        if($e->isArrowKey()){ $this->update=true;
            switch($e->codeName){
                case "Left":
                    if($this->selection["x"]>0){
                        if($e->shiftDown){
                            if(!$this->selection["s"]){ $this->selection["sx"]=$this->selection["x"];
                                $this->selection["l"]=0; }
                            if($this->selection["sx"]==$this->selection["x"]){ $this->selection["sx"]--; $this->selection["l"]++;
                            }elseif($this->selection["sx"]+$this->selection["l"]==$this->selection["x"]) $this->selection["l"]--;
                            $this->selection["s"]=true;
                        }else $this->selection["s"]=false; $this->selection["x"]--; }else $this->selection["s"]=$e->shiftDown; $this->selection["o"]=0; break;
                case "Up":
                    if($this->selection["x"]>0){
                        $o=$this->selection["o"];
                        $ww2=str::length($this->getLine($this->getIndexLine()-1))+1;
                        $px=$this->selection["x"]-str::lastPos(str::sub($this->text,0,$this->selection["x"]),"\n");
                        $x=0;
                        if($px<$ww2){
                            $o-=$ww2-$px;
                            $x=$px;
                            if($o<0){
                                $x-=$o;
                                $o=0;
                            }
                        }else{
                            $o+=$px-$ww2;
                            $x=$px;
                        }
                        if($e->shiftDown){
                            if(!$this->selection["s"]){
                                $this->selection["sx"]=$this->selection["x"]-$x;
                                $this->selection["l"]=$x;
                            }elseif($this->selection["sx"]==$this->selection["x"]){
                                $this->selection["sx"]-=$x;
                                if($this->selection["sx"]<0){
                                    $this->selection["l"]+=$this->selection["sx"];
                                    $this->selection["sx"]=0;
                                }
                                $this->selection["l"]+=$x;
                            }elseif($this->selection["sx"]+$this->selection["l"]==$this->selection["x"]){
                                $this->selection["l"]-=$x;
                                if($this->selection["l"]<0){
                                    $this->selection["sx"]+=$this->selection["l"];
                                    $this->selection["l"]=-$this->selection["l"];
                                }
                            }
                            $this->selection["s"]=$this->selection["l"]>0;
                        }else $this->selection["s"]=false;
                        $this->selection["o"]=$o;
                        $this->selection["x"]-=$x;
                        $this->selection["x"]=max($this->selection["x"],0);
                    }else $this->selection["s"]=$e->shiftDown;
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
                    }else $this->selection["s"]=$e->shiftDown;
                    $this->selection["o"]=0;
                    break;
                case "Down":
                    if($lt>$this->selection["x"]){
                        $o=$this->selection["o"];
                        $ww=str::length($this->getLine())+1;
                        $px=$this->selection["x"]-str::lastPos(str::sub($this->text,0,$this->selection["x"]),"\n");
                        $ww2=str::length($this->getLine($this->getIndexLine()+1))+1;
                        if($px<$ww2){
                            $o-=$ww2-$px;
                            $x=$ww2+($ww-$px);
                            if($o<0){
                                $x+=$o;
                                $o=0;
                            }
                        }else{
                            $o+=$px-$ww2;
                            $x=$ww2+($ww-$px);
                        }
                        if($e->shiftDown){
                            if(!$this->selection["s"]){
                                $this->selection["sx"]=$this->selection["x"];
                                $this->selection["l"]=$x;
                            }elseif($this->selection["sx"]==$this->selection["x"]){
                                $this->selection["sx"]+=$x;
                                $this->selection["l"]-=$x;
                                if($this->selection["l"]<0){
                                    $this->selection["sx"]+=$this->selection["l"];
                                    $this->selection["l"]=-$this->selection["l"];
                                }
                            }elseif($this->selection["sx"]+$this->selection["l"]==$this->selection["x"]) $this->selection["l"]=min($this->selection["l"]+$x,$lt-$this->selection["sx"]);
                            $this->selection["s"]=$this->selection["l"]>0;
                        }else $this->selection["s"]=false;
                        $this->selection["o"]=$o;
                        $this->selection["x"]+=$x;
                        $this->selection["x"] = min($this->selection["x"],$lt);
                    }else $this->selection["s"]=$e->shiftDown;
                    break;
            }
            $this->scroll();
        }elseif($e->codeName=="Backspace"){
            $this->skip=true;
            $this->text=$this->selection["s"] ? str::sub($this->text,0,$this->selection["sx"]).str::sub($this->text,$this->selection["sx"]+$this->selection["l"]) : str::sub($this->text,0,$this->selection["x"]-1).str::sub($this->text,$this->selection["x"]);
            if($this->selection["s"]){ $this->selection["s"]=false;
                if($this->selection["x"]!=$this->selection["sx"]){
                    $this->selection["x"]-=$this->selection["l"];
                    if($this->selection["x"]<0) $this->selection["x"]=0; }
            }else $this->selection["x"]-=$this->selection["x"]>0 ? 1 : 0;
            $this->editedFile(); $this->update=true; $this->scroll();
        }elseif($e->codeName=="Delete"){ $this->skip = true;
            $this->text=$this->selection["s"] ? str::sub($this->text,0,$this->selection["sx"]).str::sub($this->text,$this->selection["sx"]+$this->selection["l"]) : str::sub($this->text,0,$this->selection["x"]).str::sub($this->text,$this->selection["x"]+1);
            if($this->selection["s"]){ $this->selection["s"]=false;
                if($this->selection["x"]!=$this->selection["sx"]){
                    $this->selection["x"]-=$this->selection["l"];
                    if($this->selection["x"]<0) $this->selection["x"]=0; }
            }
            $this->editedFile(); $this->update=true;
        }elseif($e->codeName=="Home"){
            $this->update=true;
            $this->selection["x"]=str::lastPos(str::sub($this->text,0,$x=$this->selection["x"]),"\n")+1;
            if($e->shiftDown){
                if(!$this->selection["s"]){
                    $this->selection["s"]=true;
                    $this->selection["l"]=$x-$this->selection["x"];
                    $this->selection["sx"]=$this->selection["x"];
                }elseif($this->selection["sx"]!=$this->selection["x"]){
                    if($this->selection["sx"]>$this->selection["x"]){
                        if($this->selection["sx"]==$x){
                            $this->selection["l"]+=$x-$this->selection["x"];
                            $this->selection["sx"]=$this->selection["x"];
                        }else{
                            $this->selection["l"]+=$this->selection["sx"]-$this->selection["x"]-$this->selection["l"];
                            $this->selection["sx"]-=$this->selection["l"];
                        }
                    }else $this->selection["l"]+=$this->selection["x"]-$this->selection["sx"]-$this->selection["l"];
                }
            }
            $this->scroll();
        }elseif($e->codeName=="End"){
            $this->update=true;
            $x=$this->selection["x"];
            $this->selection["x"]=str::lastPos(str::sub($this->text,0,$this->selection["x"]),"\n")+str::length($this->getLine())+1;
            if($e->shiftDown){
                if($this->selection["s"]){
                    if($x==$this->selection["sx"]){
                        if($x+$this->selection["l"]>$this->selection["x"]){
                            $xm=$this->selection["x"]-$x;
                            $this->selection["sx"]+=$xm;
                            $this->selection["l"]-=$xm;
                        }else{
                            $this->selection["l"]=$this->selection["x"]-$x-$this->selection["l"];
                            $this->selection["sx"]=$this->selection["x"]-$this->selection["l"];
                        }
                    }else{
                        $this->selection["l"]=$this->selection["x"]-$this->selection["sx"];
                        $this->selection["sx"]=$this->selection["x"]-$this->selection["l"];
                    }
                }else{
                    $this->selection["l"]=$this->selection["x"]-$x;
                    $this->selection["sx"]=$this->selection["x"]-$this->selection["l"];
                }
                $this->selection["s"]=true;
            }
            $this->scroll();
        }elseif($e->codeName=="Page Up"){
            $this->update=true;
            if($e->shiftDown){
                if(!$this->selection["s"]) $this->selection["l"]=$this->selection["x"];
                else $this->selection["l"]+=$this->selection["sx"];
                $this->selection["s"]=true;
                $this->selection["sx"]=0;
            }
            $this->selection["x"]=0;
            $this->scroll();
        }elseif($e->codeName=="Page Down"){
            $this->update=true;
            if($e->shiftDown){
                if(!$this->selection["s"]) $this->selection["sx"]=$this->selection["x"];
                $this->selection["s"]=true;
                $this->selection["l"]=str::length($this->text)-$this->selection["sx"];
            }
            $this->selection["x"]=str::length($this->text);
            $this->scroll();
        }elseif($e->codeName=="Esc")
            $this->skip=true;
        elseif($e->controlDown){
            switch($e->codeName){
                case "X":
                    if($this->selection["s"]){
                        UXClipboard::setText(str::sub($this->text,$this->selection["sx"],$this->selection["sx"]+$this->selection["l"]));
                        $this->text=str::sub($this->text,0,$this->selection["sx"]).str::sub($this->text,$this->selection["sx"]+$this->selection["l"]);
                        $this->editedFile(); $this->update=true; $this->scroll();
                    }
                    break;
                case "C":
                    if($this->selection["s"]) UXClipboard::setText(str::sub($this->text,$this->selection["sx"],$this->selection["sx"]+$this->selection["l"]));
                    $this->update=true; break;
                case "V":
                    $cb = implode("\n",str::lines(UXClipboard::getText()));
                    $this->text=$this->selection["s"] ? str::sub($this->text,0,$this->selection["sx"]).$cb.str::sub($this->text,$this->selection["sx"]+$this->selection["l"]) : str::sub($this->text,0,$this->selection["x"]).$cb.str::sub($this->text,$this->selection["x"]);
                    if($this->selection["s"]){
                        if($this->selection["sx"]!=$this->selection["x"]) $this->selection["x"]-=$this->selection["l"];
                        $this->selection["s"]=false; }
                    $this->selection["x"]+=str::length($cb); $this->editedFile(); $this->update=true; $this->scroll(); $this->selection["o"]=0; break;
                case "A":
                    $this->selection["s"]=true; $this->selection["sx"]=0;
                    $this->selection["x"]=$this->selection["l"]=str::length($this->text);
                    $this->update=true; $this->scroll(); $this->selection["o"]=0; break;
                case "S":
                    $this->save();
                    break;
            }
            $this->skip=true;
        }
    }

    /**
     * @event canvas.keyPress 
     */
    function doCanvasKeyPress(UXKeyEvent $e = null){
        if($this->skip){ $this->skip=false; return; } $chr=$e->character;
        if(str::lines($chr)[0]=="") $chr=$this->getIndexLine()>=0 ? "\n".str::sub($this->getLine(),0,str::pos($this->getLine()."a",str_replace([" ","\t"],"",$this->getLine()."a")[0])) : "\n";
        $this->text = $this->selection["s"] ? str::sub($this->text,0,$this->selection["sx"]).$chr.str::sub($this->text,$this->selection["sx"]+$this->selection["l"]) : str::sub($this->text,0,$this->selection["x"]).$chr.str::sub($this->text,$this->selection["x"]);
        if($this->selection["s"]){ $this->selection["s"] = false;
            if($this->selection["x"]!=$this->selection["sx"]) $this->selection["x"]-=$this->selection["l"];
            $this->selection["x"]+=str::length($chr);
        }else $this->selection["x"]+=str::length($chr);
        if(str::length($chr)>0){ $this->editedFile(); $this->update=true; $this->scroll(); $this->selection["o"]=0; }
    }

    /**
     * @event canvas.mouseMove 
     */
    function doCanvasMouseMove(UXMouseEvent $e = null){ $this->cx=$e->x; $this->cy=$e->y; $this->update=true; }

    /**
     * @event canvas.mouseExit 
     */
    function doCanvasMouseExit(UXMouseEvent $e = null){ $this->cx=$this->cy=-1; $this->update=true; }

    /**
     * @event canvas.scroll 
     */
    function doCanvasScroll(UXScrollEvent $e = null){ if($this->xmode) $this->scrollX-=$e->deltaY; else $this->scrollY-=$e->deltaY; $this->scrollX-=$e->deltaX; $this->update=true; }
    
    private $dc = 0;
    private $sp = [0,0];
    private $dur = 0;
    private function calcX($pos){
        $li = min(floor(($pos[1]+$this->scrollY)/($this->gc->font->size+$this->settings["textOffset"])-0.25),arr::count(explode("\n",$this->text)));
        $x=0;
        for($i=0;$i<$li;$i++) $x+=str::length($this->getLine($i))+1;
        $ww=$this->gc->font->calculateTextWidth($this->getLine($i));
        $wl=str::length($this->getLine($i));
        $bglw=$this->getBGLW();
        $i=0;
        while($i<$wl and $this->gc->font->calculateTextWidth(str::sub($this->getLine($li),0,$i+1))<$pos[0]+$this->scrollX-$bglw) $i++;
        $x+=$i;
        return min($x,str::length($this->text));
    }

    /**
     * @event canvas.mouseDrag 
     */
    function doCanvasMouseDrag(UXMouseEvent $e = null){
        $w=$this->canvas->width;
        $h=$this->canvas->height;
        if($w-16<$this->sp[0] and $h-16>$this->sp[1])
            $this->scrollY=(1/($h-16)*$e->y)*$this->maxScrollY;
        elseif($h-16<$this->sp[1] and $w-16>$this->sp[0])
            $this->scrollX=(1/($w-16)*$e->x)*$this->maxScrollX;
        else{
            $x=$this->selection["x"];
            $this->selection["x"]=$this->calcX($this->sp=[$e->x,$e->y]);
            if(!$this->selection["s"]){
                $this->selection["sx"]=$this->selection["x"]-$d;
                $this->selection["l"]=0;
                $this->dur=0;
            }
            $this->dur+=$this->selection["x"]-$x;
            if($this->dur<0){
                $this->selection["sx"]=$this->selection["x"];
                $this->selection["l"]=-$this->dur;
            }else{
                $this->selection["sx"]=$this->selection["x"]-$this->dur;
                $this->selection["l"]=$this->dur;
            }
            $this->selection["s"]=$this->selection["l"]>0;
        }
        $this->update=true;
    }

    /**
     * @event canvas.mouseDown 
     */
    function doCanvasMouseDown(UXMouseEvent $e = null){
        if($this->dc==0){
            if(!($w-16<$this->sp[0] and $h-16>$this->sp[1]) and !($h-16<$this->sp[1] and $w-16>$this->sp[0])){
                $this->selection["x"]=$this->calcX($this->sp=[$e->x,$e->y]);
                $this->selection["s"]=false;
            }
            $this->update=true;
        }
        $this->dc++;
    }

    /**
     * @event canvas.mouseUp 
     */
    function doCanvasMouseUp(UXMouseEvent $e = null){ $this->dc--; }
}
