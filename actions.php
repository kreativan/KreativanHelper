<?php
/**
 *  Admin Actions
 *
 *  @author Ivan Milincic <kreativan@outlook.com>
 *  @copyright 2018 Kreativan
 *  
 *  @var action
 *  @var id
 *
*/


$action  = $this->input->get->admin_action;


if($action) {


    $id         = $this->sanitizer->selectorValue($this->input->get->id);
    $p          = $this->pages->get($id);
	$urlSegment = ($this->input->urlSegment1) ? $this->input->urlSegment1 : "";

    // Publish / Unpublish

    if($action == "publish") {

        if($p->isUnpublished()) {

            $p->of(false);
            $p->removeStatus('unpublished');
            $p->save();
            $p->of(true);

            $this->session->set("admin_status", "message");
            $this->session->set("admin_alert", "{$p->title} has been unpublished");

        } else {

            $p->of(false);
            $p->status('unpublished');
            $p->save();
            $p->of(true);

            $this->session->set("admin_status", "message");
            $this->session->set("admin_alert", "{$p->title} has been published");
        }

        $this->session->redirect("./$urlSegment");

    }


    // Trash

    if($action == "trash") {

        $p->trash();
		
        $this->session->set("admin_status", "warning");
        $this->session->set("admin_alert", "{$p->title} has been trashed");
		
        $this->session->redirect("./$urlSegment");

    }
	
	// Restore

    if($action == "restore") {

        $this->pages->restore($p);
        $this->session->redirect("./$urlSegment");

    }


    // Delete

    if($action == "delete") {

        $this->pages->delete($p);
        $this->session->redirect("./$urlSegment");

    }


}


/* =========================================================== 
    Group Actions
=========================================================== */

//
//  Group Publis Unpublish
//

if($this->input->post->admin_action_group_publish) {

    $ids = $this->sanitizer->selectorValue($this->input->post->admin_items);
    $pgs = $this->pages->find("id=$ids, include=all");
	$urlSegment = ($this->input->urlSegment1) ? $this->input->urlSegment1 : "";

    if($pgs->count) {
        foreach($pgs as $p) {

            if($p->isUnpublished()) {

                $p->of(false);
                $p->removeStatus('unpublished');
                $p->save();
                $p->of(true);

                $message = "Pages has been unpublished";

            } else {

                $p->of(false);
                $p->status('unpublished');
                $p->save();
                $p->of(true);

                $message = "Pages has been published";

            }
            
        }
    } else {
        $message = "No pages selected";
    }

    $this->session->set("admin_status", "message");
    $this->session->set("admin_alert", $message);

    $this->session->redirect("./$urlSegment");

}


//
//  Group Trash
//

if($this->input->post->admin_action_group_delete) {

    $ids = $this->sanitizer->selectorValue($this->input->post->admin_items);
    $pgs = $this->pages->find("id=$ids, include=all");
	$urlSegment = ($this->input->urlSegment1) ? $this->input->urlSegment1 : "";

    if($pgs->count) {
        foreach($pgs as $p) $p->trash();
        $message = "pages deleted";
    } else {
        $message = "No pages selected";
    }

    $this->session->set("admin_status", "message");
    $this->session->set("admin_alert", $message);

    $this->session->redirect("./$urlSegment");

}


//
//  Group Clone
//

if($this->input->post->admin_action_group_clone) {

    $ids = $this->sanitizer->selectorValue($this->input->post->admin_items);
    $pgs = $this->pages->find("id=$ids, include=all");
	$urlSegment = ($this->input->urlSegment1) ? $this->input->urlSegment1 : "";

    if($pgs->count) {
        foreach($pgs as $p) $this->pages->clone($p);
        $message = "Pages has been cloned";
    } else {
        $message = "No pages selected";
    }

    $this->session->set("admin_status", "message");
    $this->session->set("admin_alert", $message);

    $this->session->redirect("./$urlSegment");

}
