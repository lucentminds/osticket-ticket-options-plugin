<?php
//Note that ticket obj is initiated in tickets.php.
if(!defined('OSTSCPINC') || !$thisstaff || !is_object($ticket) || !$ticket->getId()) die('Invalid path');

//Make sure the staff is allowed to access the page.
if(!@$thisstaff->isStaff() || !$ticket->checkStaffPerm($thisstaff)) die('Access Denied');

//Re-use the post info on error...savekeyboards.org (Why keyboard? -> some people care about objects than users!!)
$info=($_POST && $errors)?Format::input($_POST):array();


//Get the goodies.
$dept  = $ticket->getDept();  //Dept
$role  = $thisstaff->getRole($dept);
$staff = $ticket->getStaff(); //Assigned or closed by..
$user  = $ticket->getOwner(); //Ticket User (EndUser)
$team  = $ticket->getTeam();  //Assigned team.
$sla   = $ticket->getSLA();
$lock  = $ticket->getLock();  //Ticket lock obj
if (!$lock && $cfg->getTicketLockMode() == Lock::MODE_ON_VIEW)
    $lock = $ticket->acquireLock($thisstaff->getId());
$mylock = ($lock && $lock->getStaffId() == $thisstaff->getId()) ? $lock : null;
$id    = $ticket->getId();    //Ticket ID.

//Useful warnings and errors the user might want to know!
if ($ticket->isClosed() && !$ticket->isReopenable())
    $warn = sprintf(
            __('Current ticket status (%s) does not allow the end user to reply.'),
            $ticket->getStatus());
elseif ($ticket->isAssigned()
        && (($staff && $staff->getId()!=$thisstaff->getId())
            || ($team && !$team->hasMember($thisstaff))
        ))
    $warn.= sprintf('&nbsp;&nbsp;<span class="Icon assignedTicket">%s</span>',
            sprintf(__('Ticket is assigned to %s'),
                implode('/', $ticket->getAssignees())
                ));

if (!$errors['err']) {

    if ($lock && $lock->getStaffId()!=$thisstaff->getId())
        $errors['err'] = sprintf(__('%s is currently locked by %s'),
                __('This ticket'),
                $lock->getStaffName());
    elseif (($emailBanned=Banlist::isBanned($ticket->getEmail())))
        $errors['err'] = __('Email is in banlist! Must be removed before any reply/response');
    elseif (!Validator::is_valid_email($ticket->getEmail()))
        $errors['err'] = __('EndUser email address is not valid! Consider updating it before responding');
}

$unbannable=($emailBanned) ? BanList::includes($ticket->getEmail()) : false;

if($ticket->isOverdue())
    $warn.='&nbsp;&nbsp;<span class="Icon overdueTicket">'.__('Marked overdue!').'</span>';

?>
<!-- 
<link rel="stylesheet" type="text/css" media="all" href="ajax.php/ticket_options/static/asset/ticket-view-agents.css" /> -->
<link rel="stylesheet" type="text/css" media="all" href="ajax.php/ticket_options/static/app/ticket-view-agents.css" />




<div>
    <div class="sticky bar">
       <div class="content">
        <div class="pull-right flush-right">
            <?php
            if ($thisstaff->hasPerm(Email::PERM_BANLIST)
                    || $role->hasPerm(TicketModel::PERM_EDIT)
                    || ($dept && $dept->isManager($thisstaff))) { ?>
            <span class="action-button pull-right" data-placement="bottom" data-dropdown="#action-dropdown-more" data-toggle="tooltip" title="<?php echo __('More');?>">
                <i class="icon-caret-down pull-right"></i>
                <span ><i class="icon-cog"></i></span>
            </span>
            <?php
            }

            if ($role->hasPerm(TicketModel::PERM_EDIT)) { ?>
                <span class="action-button pull-right"><a data-placement="bottom" data-toggle="tooltip" title="<?php echo __('Edit'); ?>" href="tickets.php?id=<?php echo $ticket->getId(); ?>&a=edit"><i class="icon-edit"></i></a></span>
            <?php
            } ?>
            <span class="action-button pull-right" data-placement="bottom" data-dropdown="#action-dropdown-print" data-toggle="tooltip" title="<?php echo __('Print'); ?>">
                <i class="icon-caret-down pull-right"></i>
                <a id="ticket-print" href="tickets.php?id=<?php echo $ticket->getId(); ?>&a=print"><i class="icon-print"></i></a>
            </span>
            <div id="action-dropdown-print" class="action-dropdown anchor-right">
              <ul>
                 <li><a class="no-pjax" target="_blank" href="tickets.php?id=<?php echo $ticket->getId(); ?>&a=print&notes=0"><i
                 class="icon-file-alt"></i> <?php echo __('Ticket Thread'); ?></a>
                 <li><a class="no-pjax" target="_blank" href="tickets.php?id=<?php echo $ticket->getId(); ?>&a=print&notes=1"><i
                 class="icon-file-text-alt"></i> <?php echo __('Thread + Internal Notes'); ?></a>
              </ul>
            </div>
            <?php
            // Transfer
            if ($role->hasPerm(TicketModel::PERM_TRANSFER)) {?>
            <span class="action-button pull-right">
            <a class="ticket-action" id="ticket-transfer" data-placement="bottom" data-toggle="tooltip" title="<?php echo __('Transfer'); ?>"
                data-redirect="tickets.php"
                href="#tickets/<?php echo $ticket->getId(); ?>/transfer"><i class="icon-share"></i></a>
            </span>
            <?php
            } ?>

            <?php
            // Assign
            if ($ticket->isOpen() && $role->hasPerm(TicketModel::PERM_ASSIGN)) {?>
            <span class="action-button pull-right"
                data-dropdown="#action-dropdown-assign"
                data-placement="bottom"
                data-toggle="tooltip"
                title=" <?php echo $ticket->isAssigned() ? __('Assign') : __('Reassign'); ?>"
                >
                <i class="icon-caret-down pull-right"></i>
                <a class="ticket-action" id="ticket-assign"
                    data-redirect="tickets.php"
                    href="#tickets/<?php echo $ticket->getId(); ?>/assign"><i class="icon-user"></i></a>
            </span>
            <div id="action-dropdown-assign" class="action-dropdown anchor-right">
              <ul>
                <?php
                // Agent can claim team assigned ticket
                if (!$ticket->getStaff()
                        && (!$dept->assignMembersOnly()
                            || $dept->isMember($thisstaff))
                        ) { ?>
                 <li><a class="no-pjax ticket-action"
                    data-redirect="tickets.php"
                    href="#tickets/<?php echo $ticket->getId(); ?>/claim"><i
                    class="icon-chevron-sign-down"></i> <?php echo __('Claim'); ?></a>
                <?php
                } ?>
                 <li><a class="no-pjax ticket-action"
                    data-redirect="tickets.php"
                    href="#tickets/<?php echo $ticket->getId(); ?>/assign/agents"><i
                    class="icon-user"></i> <?php echo __('Agent'); ?></a>
                 <li><a class="no-pjax ticket-action"
                    data-redirect="tickets.php"
                    href="#tickets/<?php echo $ticket->getId(); ?>/assign/teams"><i
                    class="icon-group"></i> <?php echo __('Team'); ?></a>
              </ul>
            </div>
            <?php
            } ?>
            <div id="action-dropdown-more" class="action-dropdown anchor-right">
              <ul>
                <?php
                 if ($role->hasPerm(TicketModel::PERM_EDIT)) { ?>
                    <li><a class="change-user" href="#tickets/<?php
                    echo $ticket->getId(); ?>/change-user"><i class="icon-user"></i> <?php
                    echo __('Change Owner'); ?></a></li>
                <?php
                 }

                 if($ticket->isOpen() && ($dept && $dept->isManager($thisstaff))) {

                    if($ticket->isAssigned()) { ?>
                        <li><a  class="confirm-action" id="ticket-release" href="#release"><i class="icon-user"></i> <?php
                            echo __('Release (unassign) Ticket'); ?></a></li>
                    <?php
                    }

                    if(!$ticket->isOverdue()) { ?>
                        <li><a class="confirm-action" id="ticket-overdue" href="#overdue"><i class="icon-bell"></i> <?php
                            echo __('Mark as Overdue'); ?></a></li>
                    <?php
                    }

                    if($ticket->isAnswered()) { ?>
                    <li><a class="confirm-action" id="ticket-unanswered" href="#unanswered"><i class="icon-circle-arrow-left"></i> <?php
                            echo __('Mark as Unanswered'); ?></a></li>
                    <?php
                    } else { ?>
                    <li><a class="confirm-action" id="ticket-answered" href="#answered"><i class="icon-circle-arrow-right"></i> <?php
                            echo __('Mark as Answered'); ?></a></li>
                    <?php
                    }
                } ?>
                <?php
                if ($role->hasPerm(Ticket::PERM_EDIT)) { ?>
                <li><a href="#ajax.php/tickets/<?php echo $ticket->getId();
                    ?>/forms/manage" onclick="javascript:
                    $.dialog($(this).attr('href').substr(1), 201);
                    return false"
                    ><i class="icon-paste"></i> <?php echo __('Manage Forms'); ?></a></li>
                <?php
                } ?>

<?php           if ($thisstaff->hasPerm(Email::PERM_BANLIST)) {
                     if(!$emailBanned) {?>
                        <li><a class="confirm-action" id="ticket-banemail"
                            href="#banemail"><i class="icon-ban-circle"></i> <?php echo sprintf(
                                Format::htmlchars(__('Ban Email <%s>')),
                                $ticket->getEmail()); ?></a></li>
                <?php
                     } elseif($unbannable) { ?>
                        <li><a  class="confirm-action" id="ticket-banemail"
                            href="#unbanemail"><i class="icon-undo"></i> <?php echo sprintf(
                                Format::htmlchars(__('Unban Email <%s>')),
                                $ticket->getEmail()); ?></a></li>
                    <?php
                     }
                  }
                  if ($role->hasPerm(TicketModel::PERM_DELETE)) {
                     ?>
                    <li class="danger"><a class="ticket-action" href="#tickets/<?php
                    echo $ticket->getId(); ?>/status/delete"
                    data-redirect="tickets.php"><i class="icon-trash"></i> <?php
                    echo __('Delete Ticket'); ?></a></li>
                <?php
                 }
                ?>
              </ul>
            </div>
                <?php
                if ($role->hasPerm(TicketModel::PERM_REPLY)) { ?>
                <a href="#post-reply" class="post-response action-button"
                data-placement="bottom" data-toggle="tooltip"
                title="<?php echo __('Post Reply'); ?>"><i class="icon-mail-reply"></i></a>
                <?php
                } ?>
                <a href="#post-note" id="post-note" class="post-response action-button"
                data-placement="bottom" data-toggle="tooltip"
                title="<?php echo __('Post Internal Note'); ?>"><i class="icon-file-text"></i></a>
                <?php // Status change options
                echo TicketStatus::status_options();
                ?>
           </div>
        <div class="flush-left">
             <h2><a href="tickets.php?id=<?php echo $ticket->getId(); ?>"
             title="<?php echo __('Reload'); ?>"><i class="icon-refresh"></i>
             <?php echo sprintf(__('Ticket #%s'), $ticket->getNumber()); ?></a>
            </h2>
        </div>
    </div>
  </div>
</div>
<div class="clear tixTitle has_bottom_border">
    <h3>
    <?php $subject_field = TicketForm::getInstance()->getField('subject');
        echo $subject_field->display($ticket->getSubject()); ?>
    </h3>
</div>


<?php
    if( !TicketOptionsPlugin::details_tab_enabled() )
    {
        include( TicketOptionsPlugin::resolve_view( 'ticket-view.inc-ticket_info.php' ) );
    }
?>




<?php
foreach (DynamicFormEntry::forTicket($ticket->getId()) as $form) {
    // Skip core fields shown earlier in the ticket view
    // TODO: Rewrite getAnswers() so that one could write
    //       ->getAnswers()->filter(not(array('field__name__in'=>
    //           array('email', ...))));
    $answers = $form->getAnswers()->exclude(Q::any(array(
        'field__flags__hasbit' => DynamicFormField::FLAG_EXT_STORED,
        'field__name__in' => array('subject', 'priority')
    )));
    $displayed = array();
    foreach($answers as $a) {
        if (!($v = $a->display()))
            continue;
        $displayed[] = array($a->getLocal('label'), $v);
    }
    if (count($displayed) == 0)
        continue;
    ?>
    <table class="ticket_info custom-data" cellspacing="0" cellpadding="0" width="940" border="0">
    <thead>
        <th colspan="2"><?php echo Format::htmlchars($form->getTitle()); ?></th>
    </thead>
    <tbody>
<?php
    foreach ($displayed as $stuff) {
        list($label, $v) = $stuff;
?>
        <tr>
            <td width="200"><?php
echo Format::htmlchars($label);
            ?>:</th>
            <td><?php
echo $v;
            ?></td>
        </tr>
<?php } ?>
    </tbody>
    </table>
<?php } ?>
<div class="clear"></div>

<?php
$tcount = $ticket->getThreadEntries($types)->count();
?>
<ul  class="tabs clean threads" id="ticket_tabs" >
    <li class="active"><a id="ticket-thread-tab" href="#ticket_thread"><?php
        echo sprintf(__('Ticket Thread (%d)'), $tcount); ?></a></li>

    <li>
        <a id="ticket-tasks-tab" href="#tasks" data-url="#tickets/<?= $ticket->getId() ?>/tasks">
        <?= __('Tasks') ?>
        
        <?php if ( $ticket->getNumTasks() ): ?>
        (<span id="ticket-tasks-count"><?= $ticket->getNumTasks() ?></span>)
        <?php endif ?>
        </a>
    </li>


    <?php if( TicketOptionsPlugin::details_tab_enabled() ): ?>
        <li>
            <a id="ticket-details-tab" href="#ticket_details">Details</a>
        </li>
    <?php endif ?>




</ul>

<div id="ticket_tabs_container">




<div id="ticket_thread" class="tab_content">

<?php if( TicketOptionsPlugin::staff_thread_order() == 'desc'): ?>
    <?php include( TicketOptionsPlugin::resolve_view( 'ticket-view.inc-ticket_response.php' ) ) ?>
<?php endif ?>

<?php
    // Render ticket thread
    $ticket->getThread()->render(
            array('M', 'R', 'N'),
            array(
                'html-id'   => 'ticketThread',
                'mode'      => Thread::MODE_STAFF,
                'sort'      => TicketOptionsPlugin::staff_thread_order()
                )
            );
?>

<?php if( TicketOptionsPlugin::staff_thread_order() == 'asc'): ?>
    <?php include( TicketOptionsPlugin::resolve_view( 'ticket-view.inc-ticket_response.php' ) ) ?>
<?php endif ?>

</div>




<!-- ************************************************************************-->
<?php if( TicketOptionsPlugin::details_tab_enabled() ): ?>

<div id="ticket_details" class="tab_content" style="display:none;">

    <?php include( TicketOptionsPlugin::resolve_view( 'ticket-view.inc-ticket_info.php' ) ) ?>
    <?php include( TicketOptionsPlugin::resolve_view( 'ticket-view-agents.php' ) ) ?>

</div>

<?php endif ?>
<!-- ************************************************************************-->


</div><!-- /#ticket_tabs_container -->


<div style="display:none;" class="dialog" id="print-options">
    <h3><?php echo __('Ticket Print Options');?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <form action="tickets.php?id=<?php echo $ticket->getId(); ?>"
        method="post" id="print-form" name="print-form" target="_blank">
        <?php csrf_token(); ?>
        <input type="hidden" name="a" value="print">
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <fieldset class="notes">
            <label class="fixed-size" for="notes"><?php echo __('Print Notes');?>:</label>
            <label class="inline checkbox">
            <input type="checkbox" id="notes" name="notes" value="1"> <?php echo __('Print <b>Internal</b> Notes/Comments');?>
            </label>
        </fieldset>
        <fieldset>
            <label class="fixed-size" for="psize"><?php echo __('Paper Size');?>:</label>
            <select id="psize" name="psize">
                <option value="">&mdash; <?php echo __('Select Print Paper Size');?> &mdash;</option>
                <?php
                  $psize =$_SESSION['PAPER_SIZE']?$_SESSION['PAPER_SIZE']:$thisstaff->getDefaultPaperSize();
                  foreach(Export::$paper_sizes as $v) {
                      echo sprintf('<option value="%s" %s>%s</option>',
                                $v,($psize==$v)?'selected="selected"':'', __($v));
                  }
                ?>
            </select>
        </fieldset>
        <hr style="margin-top:3em"/>
        <p class="full-width">
            <span class="buttons pull-left">
                <input type="reset" value="<?php echo __('Reset');?>">
                <input type="button" value="<?php echo __('Cancel');?>" class="close">
            </span>
            <span class="buttons pull-right">
                <input type="submit" value="<?php echo __('Print');?>">
            </span>
         </p>
    </form>
    <div class="clear"></div>
</div>
<div style="display:none;" class="dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm');?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="claim-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>claim</b> (self assign) %s?'), __('this ticket'));?>
    </p>
    <p class="confirm-action" style="display:none;" id="answered-confirm">
        <?php echo __('Are you sure you want to flag the ticket as <b>answered</b>?');?>
    </p>
    <p class="confirm-action" style="display:none;" id="unanswered-confirm">
        <?php echo __('Are you sure you want to flag the ticket as <b>unanswered</b>?');?>
    </p>
    <p class="confirm-action" style="display:none;" id="overdue-confirm">
        <?php echo __('Are you sure you want to flag the ticket as <font color="red"><b>overdue</b></font>?');?>
    </p>
    <p class="confirm-action" style="display:none;" id="banemail-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>ban</b> %s?'), $ticket->getEmail());?> <br><br>
        <?php echo __('New tickets from the email address will be automatically rejected.');?>
    </p>
    <p class="confirm-action" style="display:none;" id="unbanemail-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>remove</b> %s from ban list?'), $ticket->getEmail()); ?>
    </p>
    <p class="confirm-action" style="display:none;" id="release-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>unassign</b> ticket from <b>%s</b>?'), $ticket->getAssigned()); ?>
    </p>
    <p class="confirm-action" style="display:none;" id="changeuser-confirm">
        <span id="msg_warning" style="display:block;vertical-align:top">
        <?php echo sprintf(Format::htmlchars(__('%s <%s> will longer have access to the ticket')),
            '<b>'.Format::htmlchars($ticket->getName()).'</b>', Format::htmlchars($ticket->getEmail())); ?>
        </span>
        <?php echo sprintf(__('Are you sure you want to <b>change</b> ticket owner to %s?'),
            '<b><span id="newuser">this guy</span></b>'); ?>
    </p>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong><?php echo sprintf(
            __('Are you sure you want to DELETE %s?'), __('this ticket'));?></strong></font>
        <br><br><?php echo __('Deleted data CANNOT be recovered, including any associated attachments.');?>
    </p>
    <div><?php echo __('Please confirm to continue.');?></div>
    <form action="tickets.php?id=<?php echo $ticket->getId(); ?>" method="post" id="confirm-form" name="confirm-form">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="a" value="process">
        <input type="hidden" name="do" id="action" value="">
        <hr style="margin-top:1em"/>
        <p class="full-width">
            <span class="buttons pull-left">
                <input type="button" value="<?php echo __('Cancel');?>" class="close">
            </span>
            <span class="buttons pull-right">
                <input type="submit" value="<?php echo __('OK');?>">
            </span>
         </p>
    </form>
    <div class="clear"></div>
</div>
<script type="text/javascript">
$(function() {
    $(document).on('click', 'a.change-user', function(e) {
        e.preventDefault();
        var tid = <?php echo $ticket->getOwnerId(); ?>;
        var cid = <?php echo $ticket->getOwnerId(); ?>;
        var url = 'ajax.php/'+$(this).attr('href').substr(1);
        $.userLookup(url, function(user) {
            if(cid!=user.id
                    && $('.dialog#confirm-action #changeuser-confirm').length) {
                $('#newuser').html(user.name +' &lt;'+user.email+'&gt;');
                $('.dialog#confirm-action #action').val('changeuser');
                $('#confirm-form').append('<input type=hidden name=user_id value='+user.id+' />');
                $('#overlay').show();
                $('.dialog#confirm-action .confirm-action').hide();
                $('.dialog#confirm-action p#changeuser-confirm')
                .show()
                .parent('div').show().trigger('click');
            }
        });
    });

    // Post Reply or Note action buttons.
    $('a.post-response').click(function (e) {
        var $r = $('ul.tabs > li > a'+$(this).attr('href')+'-tab');
        if ($r.length) {
            // Make sure ticket thread tab is visiable.
            var $t = $('ul#ticket_tabs > li > a#ticket-thread-tab');
            if ($t.length && !$t.hasClass('active'))
                $t.trigger('click');
            // Make the target response tab active.
            if (!$r.hasClass('active'))
                $r.trigger('click');

            // Scroll to the response section.
            var $stop = $(document).height();
            var $s = $('div#response_options');
            if ($s.length)
                $stop = $s.offset().top-125

            $('html, body').animate({scrollTop: $stop}, 'fast');
        }

        return false;
    });

});
</script>
