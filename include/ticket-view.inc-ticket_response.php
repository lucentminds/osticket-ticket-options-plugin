<!-- Replaced by TicketOptionsPlugin -->
<?php if(!defined('INCLUDE_DIR')) die('Fatal error'); ?>

<div class="sticky bar stop actions" id="response_options">
    <ul class="tabs" id="response-tabs">
        <?php
        if ($role->hasPerm(Ticket::PERM_REPLY) && !($blockReply)) { ?>
        <li class="active <?php
            echo isset($errors['reply']) ? 'error' : ''; ?>"><a
            href="#reply" id="post-reply-tab"><?php echo __('Post Reply');?></a></li>
        <?php
        }
        if (!($blockReply)) { ?>
        <li><a href="#note" <?php
            echo isset($errors['postnote']) ?  'class="error"' : ''; ?>
            id="post-note-tab"><?php echo __('Post Internal Note');?></a></li>
        <?php
        } ?>
    </ul>
    <?php
    if ($role->hasPerm(Ticket::PERM_REPLY) && !($blockReply)) {
        $replyTo = $_POST['reply-to'] ?: 'all';
        $emailReply = ($replyTo != 'none');
        ?>
    <form id="reply" class="tab_content spellcheck exclusive save"
        data-lock-object-id="ticket/<?php echo $ticket->getId(); ?>"
        data-lock-id="<?php echo $mylock ? $mylock->getId() : ''; ?>"
        action="tickets.php?id=<?php
        echo $ticket->getId(); ?>#reply" name="reply" method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="msgId" value="<?php echo $msgId; ?>">
        <input type="hidden" name="a" value="reply">
        <input type="hidden" name="lockCode" value="<?php echo $mylock ? $mylock->getCode() : ''; ?>">
        <table style="width:100%" border="0" cellspacing="0" cellpadding="3">
            <?php
            if ($errors['reply']) {?>
            <tr><td width="120">&nbsp;</td><td class="error"><?php echo $errors['reply']; ?>&nbsp;</td></tr>
            <?php
            }?>
           <tbody id="to_sec">
           <tr>
               <td width="120">
                   <label><strong><?php echo __('From'); ?>:</strong></label>
               </td>
               <td>
                   <select id="from_email_id" name="from_email_id">
                     <?php
                     // Department email (default).
                     if (($e=$dept->getEmail())) {
                        echo sprintf('<option value="%s" selected="selected">%s</option>',
                                 $e->getId(),
                                 Format::htmlchars($e->getAddress()));
                     }
                     $staffDepts = $thisstaff->getDepts();
                     // Optional SMTP addreses user can send email via
                     if (($emails = Email::getAddresses(array('smtp' => true,
                                 'depts' => $staffDepts), false)) && count($emails)) {
                         echo '<option value=""
                             disabled="disabled">&nbsp;</option>';
                         $emailId = $_POST['from_email_id'] ?: 0;
                         foreach ($emails as $e) {
                             if ($dept->getEmail()->getId() == $e->getId())
                                 continue;
                             echo sprintf('<option value="%s" %s>%s</option>',
                                     $e->getId(),
                                      $e->getId() == $emailId ?
                                      'selected="selected"' : '',
                                      Format::htmlchars($e->getAddress()));
                         }
                     }
                     ?>
                   </select>
               </td>
           </tr>
            </tbody>
            <tbody id="recipients">
             <tr id="user-row">
                <td width="120">
                    <label><strong><?php echo __('Recipients'); ?>:</strong></label>
                </td>
                <td><a href="#tickets/<?php echo $ticket->getId(); ?>/user"
                    onclick="javascript:
                        $.userLookup('ajax.php/tickets/<?php echo $ticket->getId(); ?>/user',
                                function (user) {
                                    window.location = 'tickets.php?id='<?php $ticket->getId(); ?>
                                });
                        return false;
                        "><span ><?php
                            echo Format::htmlchars($ticket->getOwner()->getEmail()->getAddress());
                    ?></span></a>
                </td>
              </tr>
               <tr><td>&nbsp;</td>
                   <td>
                   <div style="margin-bottom:2px;">
                    <?php
                    if ($ticket->getThread()->getNumCollaborators())
                        $recipients = sprintf(__('(%d of %d)'),
                                $ticket->getThread()->getNumActiveCollaborators(),
                                $ticket->getThread()->getNumCollaborators());

                         echo sprintf('<span"><a id="show_ccs">
                                 <i id="arrow-icon" class="icon-caret-right"></i>&nbsp;%s </a>
                                 &nbsp;
                                 <a class="manage-collaborators
                                 collaborators preview noclick %s"
                                  href="#thread/%d/collaborators/1">
                                 %s</a></span>',
                                 __('Collaborators'),
                                 $ticket->getNumCollaborators()
                                  ? '' : 'hidden',
                                 $ticket->getThreadId(),
                                         sprintf('<span id="t%d-recipients">%s</span></a></span>',
                                             $ticket->getThreadId(),
                                             $recipients)
                         );
                    ?>
                   </div>
                   <div id="ccs" class="hidden">
                     <div>
                        <span style="margin: 10px 5px 1px 0;" class="faded pull-left"><?php echo __('Select or Add New Collaborators'); ?>&nbsp;</span>
                        <?php
                        if ($role->hasPerm(Ticket::PERM_REPLY) && $thread && $ticket->getId() == $thread->getObjectId()) { ?>
                        <span class="action-button pull-left" style="margin: 2px  0 5px 20px;"
                            data-dropdown="#action-dropdown-collaborators"
                            data-placement="bottom"
                            data-toggle="tooltip"
                            title="<?php echo __('Manage Collaborators'); ?>"
                            >
                            <i class="icon-caret-down pull-right"></i>
                            <a class="ticket-action" id="collabs-button"
                                data-redirect="tickets.php?id=<?php echo
                                $ticket->getId(); ?>"
                                href="#thread/<?php echo
                                $ticket->getThreadId(); ?>/collaborators/1">
                                <i class="icon-group"></i></a>
                         </span>
                         <?php
                        }  ?>
                         <span class="error">&nbsp;&nbsp;<?php echo $errors['ccs']; ?></span>
                        </div>
                        <?php
                        if ($role->hasPerm(Ticket::PERM_REPLY) && $thread && $ticket->getId() == $thread->getObjectId()) { ?>
                        <div id="action-dropdown-collaborators" class="action-dropdown anchor-right">
                          <ul>
                             <li><a class="manage-collaborators"
                                href="#thread/<?php echo
                                $ticket->getThreadId(); ?>/add-collaborator/addcc"><i
                                class="icon-plus"></i> <?php echo __('Add New'); ?></a>
                             <li><a class="manage-collaborators"
                                href="#thread/<?php echo
                                $ticket->getThreadId(); ?>/collaborators/1"><i
                                class="icon-cog"></i> <?php echo __('Manage Collaborators'); ?></a>
                          </ul>
                        </div>
                        <?php
                        } ?>
                     <div class="clear">
                      <select id="collabselection" name="ccs[]" multiple="multiple"
                          data-placeholder="<?php
                            echo __('Select Active Collaborators'); ?>">
                          <?php
                          if ($collabs = $ticket->getCollaborators()) {
                              foreach ($collabs as $c) {
                                  echo sprintf('<option value="%s" %s class="%s">%s</option>',
                                          $c->getUserId(),
                                          $c->isActive() ?
                                          'selected="selected"' : '',
                                          $c->isActive() ?
                                          'active' : 'disabled',
                                          $c->getName());
                              }
                          }
                          ?>
                      </select>
                     </div>
                 </div>
                 </td>
             </tr>
             <tr>
                <td width="120">
                    <label><?php echo __('Reply To'); ?>:</label>
                </td>
                <td>
                    <?php
                    // Supported Reply Types
                    $replyTypes = array(
                            'all'   =>  __('All Active Recipients'),
                            'user'  =>  sprintf('%s (%s)',
                                __('Ticket Owner'),
                                Format::htmlchars($ticket->getOwner()->getEmail())),
                            'none'  =>  sprintf('&mdash; %s  &mdash;',
                                __('Do Not Email Reply'))
                            );

                    $replyTo = $_POST['reply-to'] ?: 'all';
                    $emailReply = ($replyTo != 'none');
                    ?>
                    <select id="reply-to" name="reply-to">
                        <?php
                        foreach ($replyTypes as $k => $v) {
                            echo sprintf('<option value="%s" %s>%s</option>',
                                    $k,
                                    ($k == $replyTo) ?
                                    'selected="selected"' : '',
                                    $v);
                        }
                        ?>
                    </select>
                    <i class="help-tip icon-question-sign" href="#reply_types"></i>
                </td>
             </tr>
            </tbody>
            <tbody id="resp_sec">
            <tr><td colspan="2">&nbsp;</td></tr>
            <tr>
                <td width="120" style="vertical-align:top">
                    <label><strong><?php echo __('Response');?>:</strong></label>
                </td>
                <td>
                <?php
                if ($errors['response'])
                    echo sprintf('<div class="error">%s</div>',
                            $errors['response']);

                if ($cfg->isCannedResponseEnabled()) { ?>
                  <div>
                    <select id="cannedResp" name="cannedResp">
                        <option value="0" selected="selected"><?php echo __('Select a canned response');?></option>
                        <option value='original'><?php echo __('Original Message'); ?></option>
                        <option value='lastmessage'><?php echo __('Last Message'); ?></option>
                        <?php
                        if(($cannedResponses=Canned::responsesByDeptId($ticket->getDeptId()))) {
                            echo '<option value="0" disabled="disabled">
                                ------------- '.__('Premade Replies').' ------------- </option>';
                            foreach($cannedResponses as $id =>$title)
                                echo sprintf('<option value="%d">%s</option>',$id,$title);
                        }
                        ?>
                    </select>
                    </div>
                    </td></tr>
                    <tr><td colspan="2">
                <?php } # endif (canned-resonse-enabled)
                    $signature = '';
                    switch ($thisstaff->getDefaultSignatureType()) {
                    case 'dept':
                        if ($dept && $dept->canAppendSignature())
                           $signature = $dept->getSignature();
                       break;
                    case 'mine':
                        $signature = $thisstaff->getSignature();
                        break;
                    } ?>
                    <input type="hidden" name="draft_id" value=""/>
                    <textarea name="response" id="response" cols="50"
                        data-signature-field="signature" data-dept-id="<?php echo $dept->getId(); ?>"
                        data-signature="<?php
                            echo Format::htmlchars(Format::viewableImages($signature)); ?>"
                        placeholder="<?php echo __(
                        'Start writing your response here. Use canned responses from the drop-down above'
                        ); ?>"
                        rows="9" wrap="soft"
                        class="<?php if ($cfg->isRichTextEnabled()) echo 'richtext';
                            ?> draft draft-delete fullscreen" <?php
    list($draft, $attrs) = Draft::getDraftAndDataAttrs('ticket.response', $ticket->getId(), $info['response']);
    echo $attrs; ?>><?php echo ThreadEntryBody::clean($_POST ? $info['response'] : $draft);
                    ?></textarea>
                <div id="reply_form_attachments" class="attachments">
                <?php
                    print $response_form->getField('attachments')->render();
                ?>
                </div>
                </td>
            </tr>
            <tr>
                <td width="120">
                    <label for="signature" class="left"><?php echo __('Signature');?>:</label>
                </td>
                <td>
                    <?php
                    $info['signature']=$info['signature']?$info['signature']:$thisstaff->getDefaultSignatureType();
                    ?>
                    <label><input type="radio" name="signature" value="none" checked="checked"> <?php echo __('None');?></label>
                    <?php
                    if($thisstaff->getSignature()) {?>
                    <label><input type="radio" name="signature" value="mine"
                        <?php echo ($info['signature']=='mine')?'checked="checked"':''; ?>> <?php echo __('My Signature');?></label>
                    <?php
                    } ?>
                    <?php
                    if($dept && $dept->canAppendSignature()) { ?>
                    <label><input type="radio" name="signature" value="dept"
                        <?php echo ($info['signature']=='dept')?'checked="checked"':''; ?>>
                        <?php echo sprintf(__('Department Signature (%s)'), Format::htmlchars($dept->getName())); ?></label>
                    <?php
                    } ?>
                </td>
            </tr>
            <tr>
                <td width="120" style="vertical-align:top">
                    <label><strong><?php echo __('Ticket Status');?>:</strong></label>
                </td>
                <td>
                    <?php
                    $outstanding = false;
                    if ($role->hasPerm(Ticket::PERM_CLOSE)
                            && is_string($warning=$ticket->isCloseable())) {
                        $outstanding =  true;
                        echo sprintf('<div class="warning-banner">%s</div>', $warning);
                    } ?>
                    <select name="reply_status_id">
                    <?php
                    $statusId = $info['reply_status_id'] ?: $ticket->getStatusId();
                    $states = array('open');
                    if ($role->hasPerm(Ticket::PERM_CLOSE) && !$outstanding)
                        $states = array_merge($states, array('closed'));

                    foreach (TicketStatusList::getStatuses(
                                array('states' => $states)) as $s) {
                        if (!$s->isEnabled()) continue;
                        $selected = ($statusId == $s->getId());
                        echo sprintf('<option value="%d" %s>%s%s</option>',
                                $s->getId(),
                                $selected
                                 ? 'selected="selected"' : '',
                                __($s->getName()),
                                $selected
                                ? (' ('.__('current').')') : ''
                                );
                    }
                    ?>
                    </select>
                </td>
            </tr>
         </tbody>
        </table>
        <p  style="text-align:center;">
            <input class="save pending" type="submit" value="<?php echo __('Post Reply');?>">
            <input class="" type="reset" value="<?php echo __('Reset');?>">
        </p>
    </form>
    <?php
    }
    if (!($blockReply)) {
    ?>
    <form id="note" class="hidden tab_content spellcheck exclusive save"
        data-lock-object-id="ticket/<?php echo $ticket->getId(); ?>"
        data-lock-id="<?php echo $mylock ? $mylock->getId() : ''; ?>"
        action="tickets.php?id=<?php echo $ticket->getId(); ?>#note"
        name="note" method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="locktime" value="<?php echo $cfg->getLockTime() * 60; ?>">
        <input type="hidden" name="a" value="postnote">
        <input type="hidden" name="lockCode" value="<?php echo $mylock ? $mylock->getCode() : ''; ?>">
        <table width="100%" border="0" cellspacing="0" cellpadding="3">
            <?php
            if($errors['postnote']) {?>
            <tr>
                <td width="120">&nbsp;</td>
                <td class="error"><?php echo $errors['postnote']; ?></td>
            </tr>
            <?php
            } ?>
            <tr>
                <td width="120" style="vertical-align:top">
                    <label><strong><?php echo __('Internal Note'); ?>:</strong><span class='error'>&nbsp;*</span></label>
                </td>
                <td>
                    <div>
                        <div class="faded" style="padding-left:0.15em"><?php
                        echo __('Note title - summary of the note (optional)'); ?></div>
                        <input type="text" name="title" id="title" size="60" value="<?php echo $info['title']; ?>" >
                        <br/>
                        <span class="error">&nbsp;<?php echo $errors['title']; ?></span>
                    </div>
                </td></tr>
                <tr><td colspan="2">
                    <div class="error"><?php echo $errors['note']; ?></div>
                    <textarea name="note" id="internal_note" cols="80"
                        placeholder="<?php echo __('Note details'); ?>"
                        rows="9" wrap="soft"
                        class="<?php if ($cfg->isRichTextEnabled()) echo 'richtext';
                            ?> draft draft-delete fullscreen" <?php
    list($draft, $attrs) = Draft::getDraftAndDataAttrs('ticket.note', $ticket->getId(), $info['note']);
    echo $attrs; ?>><?php echo ThreadEntryBody::clean($_POST ? $info['note'] : $draft);
                        ?></textarea>
                <div class="attachments">
                <?php
                    print $note_form->getField('attachments')->render();
                ?>
                </div>
                </td>
            </tr>
            <tr><td colspan="2">&nbsp;</td></tr>
            <tr>
                <td width="120">
                    <label><?php echo __('Ticket Status');?>:</label>
                </td>
                <td>
                    <div class="faded"></div>
                    <select name="note_status_id">
                        <?php
                        $statusId = $info['note_status_id'] ?: $ticket->getStatusId();
                        $states = array('open');
                        if ($ticket->isCloseable() === true
                                && $role->hasPerm(Ticket::PERM_CLOSE))
                            $states = array_merge($states, array('closed'));
                        foreach (TicketStatusList::getStatuses(
                                    array('states' => $states)) as $s) {
                            if (!$s->isEnabled()) continue;
                            $selected = $statusId == $s->getId();
                            echo sprintf('<option value="%d" %s>%s%s</option>',
                                    $s->getId(),
                                    $selected ? 'selected="selected"' : '',
                                    __($s->getName()),
                                    $selected ? (' ('.__('current').')') : ''
                                    );
                        }
                        ?>
                    </select>
                    &nbsp;<span class='error'>*&nbsp;<?php echo $errors['note_status_id']; ?></span>
                </td>
            </tr>
        </table>

       <p style="text-align:center;">
           <input class="save pending" type="submit" value="<?php echo __('Post Note');?>">
           <input class="" type="reset" value="<?php echo __('Reset');?>">
       </p>
   </form>
   <?php } ?>
 </div>