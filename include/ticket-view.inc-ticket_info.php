<!-- Replaced by TicketOptionsPlugin -->
<?php if(!defined('INCLUDE_DIR')) die('Fatal error'); ?>

<table class="ticket_info" cellspacing="0" cellpadding="0" width="940" border="0">
    <tr>
        <td width="50%">
            <table border="0" cellspacing="0" cellpadding="4" width="100%">
                <tr>
                    <th width="100"><?php echo __('Status');?>:</th>
                    <?php
                         if ($role->hasPerm(Ticket::PERM_CLOSE)) {?>
                         <td>
                          <a class="tickets-action" data-dropdown="#action-dropdown-statuses" data-placement="bottom" data-toggle="tooltip" title="<?php echo __('Change Status'); ?>"
                              data-redirect="tickets.php?id=<?php echo $ticket->getId(); ?>"
                              href="#statuses"
                              onclick="javascript:
                                  saveDraft();"
                              >
                              <?php echo $ticket->getStatus(); ?>
                          </a>
                        </td>
                      <?php } else { ?>
                          <td><?php echo ($S = $ticket->getStatus()) ? $S->display() : ''; ?></td>
                      <?php } ?>
                </tr>
                <tr>
                    <th><?php echo __('Priority');?>:</th>
                      <?php
                      if ($role->hasPerm(Ticket::PERM_EDIT)
                        && ($pf = $ticket->getPriorityField())) { ?>
                           <td>
                             <a class="inline-edit" data-placement="bottom" data-toggle="tooltip" title="<?php echo __('Update'); ?>"
                                 href="#tickets/<?php echo $ticket->getId();?>/field/<?php echo $pf->getId();?>/edit">
                                 <span id="field_<?php echo $pf->getId(); ?>"><?php echo $pf->getAnswer()->display(); ?></span>
                             </a>
                           </td>
                      <?php } else { ?>
                           <td><?php echo $ticket->getPriority(); ?></td>
                      <?php } ?>
                </tr>
                <tr>
                    <th><?php echo __('Department');?>:</th>
                    <?php
                    if ($role->hasPerm(Ticket::PERM_TRANSFER)) {?>
                      <td>
                          <a class="ticket-action" data-placement="bottom" data-toggle="tooltip" title="<?php echo __('Transfer'); ?>"
                            data-redirect="tickets.php?id=<?php echo $ticket->getId(); ?>"
                            href="#tickets/<?php echo $ticket->getId(); ?>/transfer"
                            onclick="javascript:
                                saveDraft();"
                            ><?php echo Format::htmlchars($ticket->getDeptName()); ?>
                        </a>
                      </td>
                    <?php
                  }else {?>
                    <td><?php echo Format::htmlchars($ticket->getDeptName()); ?></td>
                  <?php } ?>
                </tr>
                <tr>
                    <th><?php echo __('Create Date');?>:</th>
                    <td><?php echo Format::datetime($ticket->getCreateDate()); ?></td>
                </tr>
            </table>
        </td>
        <td width="50%" style="vertical-align:top">
            <table border="0" cellspacing="0" cellpadding="4" width="100%">
                <tr>
                    <th width="100"><?php echo __('User'); ?>:</th>
                    <td><a href="#tickets/<?php echo $ticket->getId(); ?>/user"
                        onclick="javascript:
                            saveDraft();
                            $.userLookup('ajax.php/tickets/<?php echo $ticket->getId(); ?>/user',
                                    function (user) {
                                        $('#user-'+user.id+'-name').text(user.name);
                                        $('#user-'+user.id+'-email').text(user.email);
                                        $('#user-'+user.id+'-phone').text(user.phone);
                                        $('select#emailreply option[value=1]').text(user.name+' <'+user.email+'>');
                                    });
                            return false;
                            "><i class="icon-user"></i> <span id="user-<?php echo $ticket->getOwnerId(); ?>-name"
                            ><?php echo Format::htmlchars($ticket->getName());
                        ?></span></a>
                        <?php
                        if ($user) { ?>
                            <a href="tickets.php?<?php echo Http::build_query(array(
                                'status'=>'open', 'a'=>'search', 'uid'=> $user->getId()
                            )); ?>" title="<?php echo __('Related Tickets'); ?>"
                            data-dropdown="#action-dropdown-stats">
                            (<b><?php echo $user->getNumTickets(); ?></b>)
                            </a>
                            <div id="action-dropdown-stats" class="action-dropdown anchor-right">
                                <ul>
                                    <?php
                                    if(($open=$user->getNumOpenTickets()))
                                        echo sprintf('<li><a href="tickets.php?a=search&status=open&uid=%s"><i class="icon-folder-open-alt icon-fixed-width"></i> %s</a></li>',
                                                $user->getId(), sprintf(_N('%d Open Ticket', '%d Open Tickets', $open), $open));

                                    if(($closed=$user->getNumClosedTickets()))
                                        echo sprintf('<li><a href="tickets.php?a=search&status=closed&uid=%d"><i
                                                class="icon-folder-close-alt icon-fixed-width"></i> %s</a></li>',
                                                $user->getId(), sprintf(_N('%d Closed Ticket', '%d Closed Tickets', $closed), $closed));
                                    ?>
                                    <li><a href="tickets.php?a=search&uid=<?php echo $ticket->getOwnerId(); ?>"><i class="icon-double-angle-right icon-fixed-width"></i> <?php echo __('All Tickets'); ?></a></li>
<?php   if ($thisstaff->hasPerm(User::PERM_DIRECTORY)) { ?>
                                    <li><a href="users.php?id=<?php echo
                                    $user->getId(); ?>"><i class="icon-user
                                    icon-fixed-width"></i> <?php echo __('Manage User'); ?></a></li>
<?php   } ?>
                                </ul>
                            </div>
                            <?php
                            if ($role->hasPerm(Ticket::PERM_EDIT) && $thread && $ticket->getId() == $thread->getObjectId()) {
                                if ($thread) {
                                    $numCollaborators = $thread->getNumCollaborators();
                                    if ($thread->getNumCollaborators())
                                        $recipients = sprintf(__('%d'),
                                                $numCollaborators);
                                } else
                                  $recipients = 0;

                             echo sprintf('<span><a class="manage-collaborators preview"
                                    href="#thread/%d/collaborators/1"><span id="t%d-recipients"><i class="icon-group"></i> (%s)</span></a></span>',
                                    $ticket->getThreadId(),
                                    $ticket->getThreadId(),
                                    $recipients);
                             }?>
<?php                   } # end if ($user) ?>
                    </td>
                </tr>
                <tr>
                    <th><?php echo __('Email'); ?>:</th>
                    <td>
                        <span id="user-<?php echo $ticket->getOwnerId(); ?>-email"><?php echo $ticket->getEmail(); ?></span>
                    </td>
                </tr>
<?php   if ($user->getOrganization()) { ?>
                <tr>
                    <th><?php echo __('Organization'); ?>:</th>
                    <td><i class="icon-building"></i>
                    <?php echo Format::htmlchars($user->getOrganization()->getName()); ?>
                        <a href="tickets.php?<?php echo Http::build_query(array(
                            'status'=>'open', 'a'=>'search', 'orgid'=> $user->getOrgId()
                        )); ?>" title="<?php echo __('Related Tickets'); ?>"
                        data-dropdown="#action-dropdown-org-stats">
                        (<b><?php echo $user->getNumOrganizationTickets(); ?></b>)
                        </a>
                            <div id="action-dropdown-org-stats" class="action-dropdown anchor-right">
                                <ul>
<?php   if ($open = $user->getNumOpenOrganizationTickets()) { ?>
                                    <li><a href="tickets.php?<?php echo Http::build_query(array(
                                        'a' => 'search', 'status' => 'open', 'orgid' => $user->getOrgId()
                                    )); ?>"><i class="icon-folder-open-alt icon-fixed-width"></i>
                                    <?php echo sprintf(_N('%d Open Ticket', '%d Open Tickets', $open), $open); ?>
                                    </a></li>
<?php   }
        if ($closed = $user->getNumClosedOrganizationTickets()) { ?>
                                    <li><a href="tickets.php?<?php echo Http::build_query(array(
                                        'a' => 'search', 'status' => 'closed', 'orgid' => $user->getOrgId()
                                    )); ?>"><i class="icon-folder-close-alt icon-fixed-width"></i>
                                    <?php echo sprintf(_N('%d Closed Ticket', '%d Closed Tickets', $closed), $closed); ?>
                                    </a></li>
                                    <li><a href="tickets.php?<?php echo Http::build_query(array(
                                        'a' => 'search', 'orgid' => $user->getOrgId()
                                    )); ?>"><i class="icon-double-angle-right icon-fixed-width"></i> <?php echo __('All Tickets'); ?></a></li>
<?php   }
        if ($thisstaff->hasPerm(User::PERM_DIRECTORY)) { ?>
                                    <li><a href="orgs.php?id=<?php echo $user->getOrgId(); ?>"><i
                                        class="icon-building icon-fixed-width"></i> <?php
                                        echo __('Manage Organization'); ?></a></li>
<?php   } ?>
                                </ul>
                            </div>
                        </td>
                    </tr>
<?php   } # end if (user->org) ?>
                <tr>
                  <th><?php echo __('Source'); ?>:</th>
                  <td>
                  <?php
                         if ($role->hasPerm(Ticket::PERM_EDIT)) {
                             $source = $ticket->getField('source');?>
                    <a class="inline-edit" data-placement="bottom" data-toggle="tooltip" title="<?php echo __('Update'); ?>"
                        href="#tickets/<?php echo $ticket->getId(); ?>/field/source/edit">
                        <span id="field_source">
                        <?php echo Format::htmlchars($ticket->getSource());
                        ?></span>
                    </a>
                      <?php
                         } else {
                            echo Format::htmlchars($ticket->getSource());
                        }

                    if (!strcasecmp($ticket->getSource(), 'Web') && $ticket->getIP())
                        echo '&nbsp;&nbsp; <span class="faded">('.Format::htmlchars($ticket->getIP()).')</span>';
                    ?>
                 </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br>
<table class="ticket_info" cellspacing="0" cellpadding="0" width="940" border="0">
    <tr>
        <td width="50%">
            <table cellspacing="0" cellpadding="4" width="100%" border="0">
                <?php
                if($ticket->isOpen()) { ?>
                <tr>
                    <th width="100"><?php echo __('Assigned To');?>:</th>
                    <?php
                    if ($role->hasPerm(Ticket::PERM_ASSIGN)) {?>
                    <td>
                        <a class="inline-edit" data-placement="bottom" data-toggle="tooltip" title="<?php echo __('Update'); ?>"
                            href="#tickets/<?php echo $ticket->getId(); ?>/assign">
                            <span id="field_assign">
                                <?php if($ticket->isAssigned())
                                        echo Format::htmlchars(implode('/', $ticket->getAssignees()));
                                      else
                                        echo '<span class="faded">&mdash; '.__('Unassigned').' &mdash;</span>';
                        ?></span>
                        </a>
                    </td>
                    <?php
                    } else { ?>
                    <td>
                      <?php
                      if($ticket->isAssigned())
                          echo Format::htmlchars(implode('/', $ticket->getAssignees()));
                      else
                          echo '<span class="faded">&mdash; '.__('Unassigned').' &mdash;</span>';
                      ?>
                    </td>
                    <?php
                    } ?>
                </tr>
                <?php
                } else { ?>
                <tr>
                    <th width="100"><?php echo __('Closed By');?>:</th>
                    <td>
                        <?php
                        if(($staff = $ticket->getStaff()))
                            echo Format::htmlchars($staff->getName());
                        else
                            echo '<span class="faded">&mdash; '.__('Unknown').' &mdash;</span>';
                        ?>
                    </td>
                </tr>
                <?php
                } ?>
                <tr>
                    <th><?php echo __('SLA Plan');?>:</th>
                    <td>
                    <?php
                         if ($role->hasPerm(Ticket::PERM_EDIT)) {
                             $slaField = $ticket->getField('sla'); ?>
                          <a class="inline-edit" data-placement="bottom" data-toggle="tooltip" title="<?php echo __('Update'); ?>"
                          href="#tickets/<?php echo $ticket->getId(); ?>/field/sla/edit">
                          <span id="field_sla"><?php echo $sla ?: __('None'); ?></span>
                      </a>
                      <?php } else { ?>
                        <span id="field_sla"><?php echo $sla ?: __('None'); ?></span>
                      <?php } ?>
                    </td>
                </tr>
                <?php
                if($ticket->isOpen()){ ?>
                <tr>
                    <th><?php echo __('Due Date');?>:</th>
                    <?php
                         if ($role->hasPerm(Ticket::PERM_EDIT)) {
                             $duedate = $ticket->getField('duedate'); ?>
                           <td>
                      <a class="inline-edit" data-placement="bottom" data-toggle="tooltip"
                          title="<?php echo __('Update'); ?>"
                          href="#tickets/<?php echo $ticket->getId();
                           ?>/field/duedate/edit">
                           <?php $due_date = Format::datetime($ticket->getEstDueDate()); ?>
                           <span id="field_duedate" <?php if (!$due_date) echo 'class="faded"'; ?>>
                               <?php echo $due_date ?: '&mdash;'.__('Empty').'&mdash;'; ?>
                           </span>
                      </a>
                           </td>
                      <?php } else { ?>
                           <td><?php echo Format::datetime($ticket->getEstDueDate()); ?></td>
                      <?php } ?>
                </tr>
                <?php
                }else { ?>
                <tr>
                    <th><?php echo __('Close Date');?>:</th>
                    <td><?php echo Format::datetime($ticket->getCloseDate()); ?></td>
                </tr>
                <?php
                }
                ?>
            </table>
        </td>
        <td width="50%">
            <table cellspacing="0" cellpadding="4" width="100%" border="0">
                <tr>
                    <th width="100"><?php echo __('Help Topic');?>:</th>
                      <?php
                           if ($role->hasPerm(Ticket::PERM_EDIT)) {
                               $topic = $ticket->getField('topic'); ?>
                             <td>
                        <a class="inline-edit" data-placement="bottom"
                            data-toggle="tooltip" title="<?php echo __('Update'); ?>"
                            href="#tickets/<?php echo $ticket->getId(); ?>/field/topic/edit">
                            <span id="field_topic">
                                <?php echo $ticket->getHelpTopic() ?: __('None'); ?>
                            </span>
                        </a>
                      </td>
                        <?php } else { ?>
                             <td><?php echo Format::htmlchars($ticket->getHelpTopic()); ?></td>
                        <?php } ?>
                </tr>
                <tr>
                    <th nowrap><?php echo __('Last Message');?>:</th>
                    <td><?php echo Format::datetime($ticket->getLastMsgDate()); ?></td>
                </tr>
                <tr>
                    <th nowrap><?php echo __('Last Response');?>:</th>
                    <td><?php echo Format::datetime($ticket->getLastRespDate()); ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br>