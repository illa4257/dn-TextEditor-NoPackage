<?php
namespace app\forms;

use std, gui, framework, app;


class about extends AbstractForm
{

    /**
     * @event link.action 
     */
    function doLinkAction(UXEvent $e = null)
    {    
        $this->toast("Ссылка открыта в браузере");
        browse("https://github.com/illa4257/dn-TextEditor-NoPackage");
    }

    /**
     * @event linkAlt.action 
     */
    function doLinkAltAction(UXEvent $e = null)
    {
        $this->toast("Ссылка открыта в браузере");
        browse("https://hub.develnext.org/project/jVezjlJACbVB");
    }

}
