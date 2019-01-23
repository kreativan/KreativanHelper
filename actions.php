<?php
/**
 *  Admin Actions
 *
 *  @author Ivan Milincic <kreativan@outlook.com>
 *  @copyright 2018 Kreativan
 *  
 *  @var action
 *
*/


$action  = $this->input->get->action;


if($action) {


    $id         = $this->sanitizer->selectorValue($this->input->get->id);
    $p          = $this->pages->get($id);


    // Publish / Unpublish

    if($action == "publish") {

        if($p->isUnpublished()) {

            $p->of(false);
            $p->removeStatus('unpublished');
            $p->save();
            $p->of(true);

            $this->session->set("status", "message");
            $this->session->set("alert", "{$p->title} has been unpublished");

        } else {

            $p->of(false);
            $p->status('unpublished');
            $p->save();
            $p->of(true);

            $this->session->set("status", "message");
            $this->session->set("alert", "{$p->title} has been published");
        }

        $this->session->redirect("./");

    }


    // Trash

    if($action == "trash") {

        $p->trash();
        $this->session->set("status", "warning");

        $this->session->set("alert", "{$p->title} has been deleted");
        $this->session->redirect("./");

    }


}