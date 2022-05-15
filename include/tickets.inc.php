<!-- Replaced by TicketOptionsPlugin -->
<link type="text/css" rel="stylesheet" href="ajax.php/ticket_options/static/asset/show-all-ticket-columns.css"/>

<?php
$search = SavedSearch::create();
$tickets = TicketModel::objects();
$clear_button = false;
$view_all_tickets = $date_header = $date_col = false;

// Make sure the cdata materialized view is available
TicketForm::ensureDynamicDataView();

// Figure out REFRESH url — which might not be accurate after posting a
// response
list($path,) = explode('?', $_SERVER['REQUEST_URI'], 2);
$args = array();
parse_str($_SERVER['QUERY_STRING'], $args);

// Remove commands from query
unset($args['id']);
if ($args['a'] !== 'search') unset($args['a']);

$refresh_url = $path . '?' . http_build_query($args);

$sort_options = array(
    'priority,updated' =>   __('Priority + Most Recently Updated'),
    'updated' =>            __('Most Recently Updated'),
    'priority,created' =>   __('Priority + Most Recently Created'),
    'due' =>                __('Due Date'),
    'priority,due' =>       __('Priority + Due Date'),
    'number' =>             __('Ticket Number'),
    'answered' =>           __('Most Recently Answered'),
    'closed' =>             __('Most Recently Closed'),
    'hot' =>                __('Longest Thread'),
    'relevance' =>          __('Relevance'),
);

// Queues columns

$queue_columns = array(
        'number' => array(
            'width' => '7.4%',
            'heading' => __('Number'),
            ),
        'subject' => array(
            'width' => '29.8%',
            'heading' => __('Subject'),
            'sort_col' => 'cdata__subject',
            ),
        'name' => array(
            'width' => '18.1%',
            'heading' => __('From'),
            'sort_col' =>  'user__name',
            ),
        'status' => array(
            'width' => '8.4%',
            'heading' => __('Status'),
            'sort_col' => 'status_id',
            ),
        'priority' => array(
            'width' => '8.4%',
            'heading' => __('Priority'),
            'sort_col' => 'cdata__:priority__priority_urgency',
            ),
        'assignee' => array(
            'width' => '16%',
            'heading' => __('Agent'),
            // <!-- Replaced by TicketOptionsPlugin -->
            'sort_col' => 'assignee',
            ),
        // <!-- Replaced by TicketOptionsPlugin -->
        // 'dept' => array(
        //     'width' => '16%',
        //     'heading' => __('Department'),
        //     'sort_col'  => 'dept__name',
        //     ),
        // <!-- Replaced by TicketOptionsPlugin -->
        'date' => array(
            'width' => '14.6%',
            'heading' => __('Date Created'),
            'sort_col' => 'created',
            ),
        );

$use_subquery = true;

// Figure out the queue we're viewing
$queue_key = sprintf('::Q:%s', ObjectModel::OBJECT_TYPE_TICKET);
$queue_name = $_SESSION[$queue_key] ?: '';

switch ($queue_name) {
case 'closed':
    $status='closed';
    $results_type=__('Closed Tickets');
    $showassigned=true; //closed by.
    $queue_sort_options = array('closed', 'priority,due', 'due',
        'priority,updated', 'priority,created', 'answered', 'number', 'hot');
    break;
case 'overdue':
    $status='open';
    $results_type=__('Overdue Tickets');
    $tickets->filter(array('isoverdue'=>1));
    $queue_sort_options = array('priority,due', 'due', 'priority,updated',
        'updated', 'answered', 'priority,created', 'number', 'hot');
    break;
case 'assigned':
    $status='open';
    $staffId=$thisstaff->getId();
    $results_type=__('My Tickets');
    $tickets->filter(Q::any(array(
        'staff_id'=>$thisstaff->getId(),
        Q::all(array('staff_id' => 0, 'team_id__gt' => 0)),
    )));
    $queue_sort_options = array('updated', 'priority,updated',
        'priority,created', 'priority,due', 'due', 'answered', 'number',
        'hot');
    break;
case 'answered':
    $status='open';
    $showanswered=true;
    $results_type=__('Answered Tickets');
    $tickets->filter(array('isanswered'=>1));
    $queue_sort_options = array('answered', 'priority,updated', 'updated',
        'priority,created', 'priority,due', 'due', 'number', 'hot');
    break;
default:
case 'search':
    $queue_sort_options = array('priority,updated', 'priority,created',
        'priority,due', 'due', 'updated', 'answered',
        'closed', 'number', 'hot');
    // Consider basic search
    if ($_REQUEST['query']) {
        $results_type=__('Search Results');
        // Use an index if possible
        if ($_REQUEST['search-type'] == 'typeahead') {
            if (Validator::is_email($_REQUEST['query'])) {
                $tickets = $tickets->filter(array(
                    'user__emails__address' => $_REQUEST['query'],
                ));
            }
            elseif ($_REQUEST['query']) {
                $tickets = $tickets->filter(array(
                    'number' => $_REQUEST['query'],
                ));
            }
        }
        elseif (isset($_REQUEST['query'])
            && ($q = trim($_REQUEST['query']))
            && strlen($q) > 2
        ) {
            // [Search] click, consider keywords
            $__tickets = $ost->searcher->find($q, $tickets);
            if (!count($__tickets) && preg_match('`\w$`u', $q)) {
                // Do wildcard search if no hits
                $__tickets = $ost->searcher->find($q.'*', $tickets);
            }
            $tickets = $__tickets;
            $has_relevance = true;
        }
        // Clear sticky search queue
        unset($_SESSION[$queue_key]);
        break;
    }
    // Apply user filter
    elseif (isset($_GET['uid']) && ($user = User::lookup($_GET['uid']))) {
        $tickets->filter(array('user__id'=>$_GET['uid']));
        $results_type = sprintf('%s — %s', __('Search Results'),
            $user->getName());
        if (isset($_GET['status']))
            $status = $_GET['status'];
        // Don't apply normal open ticket
        break;
    }
    elseif (isset($_GET['orgid']) && ($org = Organization::lookup($_GET['orgid']))) {
        $tickets->filter(array('user__org_id'=>$_GET['orgid']));
        $results_type = sprintf('%s — %s', __('Search Results'),
            $org->getName());
        if (isset($_GET['status']))
            $status = $_GET['status'];
        // Don't apply normal open ticket
        break;
    } elseif (isset($_SESSION['advsearch'])) {
        $form = $search->getFormFromSession('advsearch');
        $tickets = $search->mangleQuerySet($tickets, $form);
        $view_all_tickets = $thisstaff->hasPerm(SearchBackend::PERM_EVERYTHING);
        $results_type=__('Advanced Search')
            . '<a class="action-button" style="font-size: 15px;" href="?clear_filter"><i style="top:0" class="icon-ban-circle"></i> <em>' . __('clear') . '</em></a>';
        foreach ($form->getFields() as $sf) {
            if ($sf->get('name') == 'keywords' && $sf->getClean()) {
                $has_relevance = true;
                break;
            }
        }
        break;
    }
    // Fall-through and show open tickets
case 'open':
    $status='open';
    $queue_name = $queue_name ?: 'open';
    $results_type=__('Open Tickets');
    if (!$cfg->showAnsweredTickets())
        $tickets->filter(array('isanswered'=>0));
    $queue_sort_options = array('priority,updated', 'updated',
        'priority,due', 'due', 'priority,created', 'answered', 'number',
        'hot');
    break;
}

// Open queues _except_ assigned should respect showAssignedTickets()
// settings
if ($status != 'closed' && $queue_name != 'assigned') {
    $hideassigned = ($cfg && !$cfg->showAssignedTickets()) && !$thisstaff->showAssignedTickets();
    $showassigned = !$hideassigned;
    if ($queue_name == 'open' && $hideassigned)
        $tickets->filter(array('staff_id'=>0, 'team_id'=>0));
}

// Apply primary ticket status
if ($status)
    $tickets->filter(array('status__state'=>$status));

// Impose visibility constraints
// ------------------------------------------------------------
if (!$view_all_tickets) {
    // -- Open and assigned to me
    $assigned = Q::any(array(
        'staff_id' => $thisstaff->getId(),
    ));
    // -- Open and assigned to a team of mine
    if ($teams = array_filter($thisstaff->getTeams()))
        $assigned->add(array('team_id__in' => $teams));

    $visibility = Q::any(new Q(array('status__state'=>'open', $assigned)));

    // -- Routed to a department of mine
    if (!$thisstaff->showAssignedOnly() && ($depts=$thisstaff->getDepts()))
        $visibility->add(array('dept_id__in' => $depts));

    $tickets->filter(Q::any($visibility));
}

// TODO :: Apply requested quick filter

// Apply requested pagination
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$count = $tickets->count();
$pageNav = new Pagenate($count, $page, PAGE_LIMIT);
$pageNav->setURL('tickets.php', $args);
$tickets = $pageNav->paginate($tickets);

// Apply requested sorting
$queue_sort_key = sprintf(':Q%s:%s:sort', ObjectModel::OBJECT_TYPE_TICKET, $queue_name);

// If relevance is available, use it as the default
if ($has_relevance) {
    array_unshift($queue_sort_options, 'relevance');
}
elseif ($_SESSION[$queue_sort_key][0] == 'relevance') {
    unset($_SESSION[$queue_sort_key]);
}

if (isset($_GET['sort'])) {
    $_SESSION[$queue_sort_key] = array(
            Format::htmlchars($_GET['sort']),
            Format::htmlchars($_GET['dir'])
        );
}
elseif (!isset($_SESSION[$queue_sort_key])) {
    $_SESSION[$queue_sort_key] = array($queue_sort_options[0], 0);
}

list($sort_cols, $sort_dir) = $_SESSION[$queue_sort_key];
$orm_dir = $sort_dir ? QuerySet::ASC : QuerySet::DESC;
$orm_dir_r = $sort_dir ? QuerySet::DESC : QuerySet::ASC;

switch ($sort_cols) {
case 'number':
    $queue_columns['number']['sort_dir'] = $sort_dir;
    $tickets->extra(array(
        'order_by'=>array(
            array(SqlExpression::times(new SqlField('number'), 1), $orm_dir)
        )
    ));
    break;

case 'priority,created':
    $tickets->order_by(($sort_dir ? '-' : '') . 'cdata__:priority__priority_urgency');
    // Fall through to columns for `created`
case 'created':
    $queue_columns['date']['heading'] = __('Date Created');
    $queue_columns['date']['sort_col'] = $date_col = 'created';
    $tickets->values('created');
    $tickets->order_by($sort_dir ? 'created' : '-created');
    break;

case 'priority,due':
    $tickets->order_by('cdata__:priority__priority_urgency', $orm_dir_r);
    // Fall through to add in due date filter
case 'due':
    $queue_columns['date']['heading'] = __('Due Date');
    $queue_columns['date']['sort'] = 'due';
    $queue_columns['date']['sort_col'] = $date_col = 'est_duedate';
    $tickets->values('est_duedate');
    $tickets->order_by(SqlFunction::COALESCE(new SqlField('est_duedate'), 'zzz'), $orm_dir_r);
    break;

case 'closed':
    $queue_columns['date']['heading'] = __('Date Closed');
    $queue_columns['date']['sort'] = $sort_cols;
    $queue_columns['date']['sort_col'] = $date_col = 'closed';
    $queue_columns['date']['sort_dir'] = $sort_dir;
    $tickets->values('closed');
    $tickets->order_by('closed', $orm_dir);
    break;

case 'answered':
    $queue_columns['date']['heading'] = __('Last Response');
    $queue_columns['date']['sort'] = $sort_cols;
    $queue_columns['date']['sort_col'] = $date_col = 'thread__lastresponse';
    $queue_columns['date']['sort_dir'] = $sort_dir;
    $date_fallback = '<em class="faded">'.__('unanswered').'</em>';
    $tickets->order_by('thread__lastresponse', $orm_dir);
    $tickets->values('thread__lastresponse');
    break;

case 'hot':
    $tickets->order_by('thread_count', $orm_dir);
    $tickets->annotate(array(
        'thread_count' => SqlAggregate::COUNT('thread__entries'),
    ));
    break;

case 'relevance':
    $tickets->order_by(new SqlCode('__relevance__'), $orm_dir);
    break;

case 'assignee':
    $tickets->order_by('staff__lastname', $orm_dir);
    $tickets->order_by('staff__firstname', $orm_dir);
    $tickets->order_by('team__name', $orm_dir);
    $queue_columns['assignee']['sort_dir'] = $sort_dir;
    break;

default:
    if ($sort_cols && isset($queue_columns[$sort_cols])) {
        $queue_columns[$sort_cols]['sort_dir'] = $sort_dir;
        if (isset($queue_columns[$sort_cols]['sort_col']))
            $sort_cols = $queue_columns[$sort_cols]['sort_col'];
        $tickets->order_by($sort_cols, $orm_dir);
        break;
    }

case 'priority,updated':
    $tickets->order_by('cdata__:priority__priority_urgency', $orm_dir_r);
    // Fall through for columns defined for `updated`
case 'updated':
    $queue_columns['date']['heading'] = __('Last Updated');
    $queue_columns['date']['sort'] = $sort_cols;
    $queue_columns['date']['sort_col'] = $date_col = 'lastupdate';
    $tickets->order_by('lastupdate', $orm_dir);
    break;
}

if (in_array($sort_cols, array('created', 'due', 'updated')))
    $queue_columns['date']['sort_dir'] = $sort_dir;

// Rewrite $tickets to use a nested query, which will include the LIMIT part
// in order to speed the result
$orig_tickets = clone $tickets;
$tickets2 = TicketModel::objects();
$tickets2->values = $tickets->values;
$tickets2->filter(array('ticket_id__in' => $tickets->values_flat('ticket_id')));

// Transfer the order_by from the original tickets
$tickets2->order_by($orig_tickets->getSortFields());
$tickets = $tickets2;

// Save the query to the session for exporting
$_SESSION[':Q:tickets'] = $tickets;

TicketForm::ensureDynamicDataView();

// Select pertinent columns
// ------------------------------------------------------------
$tickets->values('lock__staff_id', 'staff_id', 'isoverdue', 'team_id',
'ticket_id', 'number', 'cdata__subject', 'user__default_email__address',
'source', 'cdata__:priority__priority_color', 'cdata__:priority__priority_desc', 'status_id', 'status__name', 'status__state', 'dept_id', 'dept__name', 'user__name', 'lastupdate', 'isanswered', 'staff__firstname', 'staff__lastname', 'team__name');

// Add in annotations
$tickets->annotate(array(
    'collab_count' => TicketThread::objects()
        ->filter(array('ticket__ticket_id' => new SqlField('ticket_id', 1)))
        ->aggregate(array('count' => SqlAggregate::COUNT('collaborators__id'))),
    'attachment_count' => TicketThread::objects()
        ->filter(array('ticket__ticket_id' => new SqlField('ticket_id', 1)))
        ->filter(array('entries__attachments__inline' => 0))
        ->aggregate(array('count' => SqlAggregate::COUNT('entries__attachments__id'))),
    'thread_count' => TicketThread::objects()
        ->filter(array('ticket__ticket_id' => new SqlField('ticket_id', 1)))
        ->exclude(array('entries__flags__hasbit' => ThreadEntry::FLAG_HIDDEN))
        ->aggregate(array('count' => SqlAggregate::COUNT('entries__id'))),
));


// Make sure we're only getting active locks
$tickets->constrain(array('lock' => array(
                'lock__expire__gt' => SqlFunction::NOW())));

?>

<!-- SEARCH FORM START -->
<div id='basic_search'>
  <div class="pull-right" style="height:25px">
    <span class="valign-helper"></span>
    <?php
    require STAFFINC_DIR.'templates/queue-sort.tmpl.php';
    ?>
  </div>
    <form action="tickets.php" method="get" onsubmit="javascript:
  $.pjax({
    url:$(this).attr('action') + '?' + $(this).serialize(),
    container:'#pjax-container',
    timeout: 2000
  });
return false;">
    <input type="hidden" name="a" value="search">
    <input type="hidden" name="search-type" value=""/>
    <div class="attached input">
      <input type="text" class="basic-search" data-url="ajax.php/tickets/lookup" name="query"
        autofocus size="30" value="<?php echo Format::htmlchars($_REQUEST['query'], true); ?>"
        autocomplete="off" autocorrect="off" autocapitalize="off">
      <button type="submit" class="attached button"><i class="icon-search"></i>
      </button>
    </div>
    <a href="#" onclick="javascript:
        $.dialog('ajax.php/tickets/search', 201);"
        >[<?php echo __('advanced'); ?>]</a>
        <i class="help-tip icon-question-sign" href="#advanced"></i>
    </form>
</div>
<!-- SEARCH FORM END -->
<div class="clear"></div>
<div style="margin-bottom:20px; padding-top:5px;">
    <div class="sticky bar opaque">
        <div class="content">
            <div class="pull-left flush-left">
                <h2><a href="<?php echo $refresh_url; ?>"
                    title="<?php echo __('Refresh'); ?>"><i class="icon-refresh"></i> <?php echo
                    $results_type; ?></a></h2>
            </div>
            <div class="pull-right flush-right">
            <?php
            if ($count) {
                Ticket::agentActions($thisstaff, array('status' => $status));
            }?>
            </div>
        </div>
    </div>
</div>
<div class="clear"></div>
<form action="tickets.php" method="POST" name='tickets' id="tickets">
<?php csrf_token(); ?>
 <input type="hidden" name="a" value="mass_process" >
 <input type="hidden" name="do" id="action" value="" >
 <input type="hidden" name="status" value="<?php echo
 Format::htmlchars($_REQUEST['status'], true); ?>" >

 <!-- Replaced by TicketOptionsPlugin -->
 <table class="list" border="0" cellspacing="1" cellpadding="2" width="100%">
    <thead>
        <tr>
            <?php
            if ($thisstaff->canManageTickets()) { ?>
	        <th width="2%">&nbsp;</th>
            <?php } ?>

            <?php
            // Swap some columns based on the queue.
            if ( $showassigned ) {
                unset($queue_columns['dept']);
                if (!strcasecmp($status,'closed'))
                    $queue_columns['assignee']['heading'] =  __('Closed By');
                else
                    $queue_columns['assignee']['heading'] =  __('Assigned To');
            } else {
                unset($queue_columns['assignee']);
            }

            // <!-- Replaced by TicketOptionsPlugin -->
            if( !TicketOptionsPlugin::show_all_ticket_columns() )
            {
                if ($search && !$status)
                {
                    unset($queue_columns['priority']);
                }
                else
                {
                    unset($queue_columns['status']);
                }
            }

            // Query string
            unset($args['sort'], $args['dir'], $args['_pjax']);
            $qstr = Http::build_query($args);
            
            
            // <!-- Replaced by TicketOptionsPlugin -->
            
            ?>
            <?php foreach( $queue_columns as $k => $column ): ?>
            <?php
                // Show headers
                $column_sort_dir = isset($column['sort_dir'])
                    ? ($column['sort_dir'] ? 'asc': 'desc') : '';
            ?>
            <th class="ticket__column__<?= $k ?>" width="<?= $column['width'] ?>">
            
                <?php if( $column[ 'sort_col' ] ): /** Create arrows for column sorting */?>
                <?php
                    $c_column_sort_href = '?sort='.( $column['sort'] ?: $k )
                        . '&dir='.( $column['sort_dir'] ? 0 : 1 )
                        . '&'.$qstr;
                ?>
                <a href="<?= $c_column_sort_href ?>" class="<?= $column_sort_dir ?>">
                <?= $column['heading'] ?>

                </a>

                <?php else: /** Do not sort this column */?>
                <?= $column['heading'] ?>

                <?php endif ?>
            </th>

            <?php endforeach ?>
            
        </tr>
     </thead>
     <tbody>
        <?php
        // Setup Subject field for display
        $subject_field = TicketForm::getInstance()->getField('subject');
        $class = "row1";
        $total=0;
        $ids=($errors && $_POST['tids'] && is_array($_POST['tids']))?$_POST['tids']:null;

        // <!-- Replaced by TicketOptionsPlugin -->
        ?>
        <?php foreach ($tickets as $T): ?>
        <?php
            //echo '<pre>'.print_r( $T, 1 ).'</pre>';
            $total += 1;
                $tag=$T['staff_id']?'assigned':'openticket';
                $flag=null;
                if($T['lock__staff_id'] && $T['lock__staff_id'] != $thisstaff->getId())
                    $flag='locked';
                elseif($T['isoverdue'])
                    $flag='overdue';
                // <!-- Replaced by TicketOptionsPlugin -->
                // $lc='';
                // if ($showassigned) {
                //     if ($T['staff_id'])
                //         $lc = new AgentsName($T['staff__firstname'].' '.$T['staff__lastname']);
                //     elseif ($T['team_id'])
                //         $lc = Team::getLocalById($T['team_id'], 'name', $T['team__name']);
                // }
                // else {
                //     $lc = Dept::getLocalById($T['dept_id'], 'name', $T['dept__name']);
                // }
                $tid=$T['number'];
                $subject = $subject_field->display($subject_field->to_php($T['cdata__subject']));
                $threadcount=$T['thread_count'];
                if(!strcasecmp($T['status__state'],'open') && !$T['isanswered'] && !$T['lock__staff_id']) {
                    $tid=sprintf('<b>%s</b>',$tid);
                }
                ?>
            <tr id="<?php echo $T['ticket_id']; ?>">
                <?php if($thisstaff->canManageTickets()) {

                    $sel=false;
                    if($ids && in_array($T['ticket_id'], $ids))
                        $sel=true;
                    ?>
                <td align="center" class="nohover">
                    <input class="ckb" type="checkbox" name="tids[]"
                        value="<?php echo $T['ticket_id']; ?>" <?php echo $sel?'checked="checked"':''; ?>>
                </td>

                <?php } ?>


                <!-- Replaced by TicketOptionsPlugin -->
                
                <?php foreach( $queue_columns as $c_column_id => $a_column_value ): ?>


                    <?php switch( $c_column_id ):

                    case 'number': ?>
                        <td class="ticket__column__<?= $c_column_id ?>" title="<?php echo $T['user__default_email__address']; ?>" nowrap>
                        <a class="Icon <?php echo strtolower($T['source']); ?>Ticket preview"
                        title="Preview Ticket"
                        href="tickets.php?id=<?php echo $T['ticket_id']; ?>"
                        data-preview="#tickets/<?php echo $T['ticket_id']; ?>/preview"
                        ><?php echo $tid; ?></a></td>


                    <!-- Replaced by TicketOptionsPlugin -->
                    <?php break; ?>

                    

                    <?php case 'date': ?>
                    <td class="ticket__column__<?= $c_column_id ?>" align="center" nowrap>
                    <?php echo Format::datetime($T[$date_col ?: 'lastupdate']) ?: $date_fallback; ?>
                    </td>
                    <?php break; ?>


                    <?php case 'subject': ?>
                    <td class="ticket__column__<?= $c_column_id ?>">
                        <div class="--flex">
                            <a href="?id=<?= $T['ticket_id'] ?>" title="<?= ucfirst( $flag ) ?> Ticket">
                            <?= $subject ?>
                            </a>


                            <div>
                            <?php if( $T['attachment_count'] ): ?>
                            <i class="small icon-paperclip icon-flip-horizontal" data-toggle="tooltip"
                                title="<?= $T['attachment_count'] ?>"></i>

                            <?php endif ?>


                            <!-- Replaced by TicketOptionsPlugin -->
                            <?php if( $threadcount > 1 ): ?>
                            <span class="pull-right faded-more"><i class="icon-comments-alt"></i>
                                <small><?= $threadcount; ?></small>
                            </span>

                            <?php endif ?>

                            </div>
                        </div>

                    </td>
                    <?php
                    // <!-- Replaced by TicketOptionsPlugin -->
                    break;
                    ?>


                    <?php case 'name': ?>
                    <td class="ticket__column__<?= $c_column_id ?>" nowrap>
                        <div><?php
                        if ($T['collab_count'])
                            echo '<span class="pull-right faded-more" data-toggle="tooltip" title="'
                                .$T['collab_count'].'"><i class="icon-group"></i></span>';
                        ?><span class="truncate" style="max-width:<?php
                            echo $T['collab_count'] ? '150px' : '170px'; ?>"><?php
                        $un = new UsersName($T['user__name']);
                            echo Format::htmlchars($un);
                    ?></span></div></td>
                    <?php break; ?>
                    <!-- Replaced by TicketOptionsPlugin -->

                    <?php case 'status': ?>
                        <?php if( TicketOptionsPlugin::show_all_ticket_columns() || ($search && !$status) ): ?>
                            <?php
                                $displaystatus = TicketStatus::getLocalById($T['status_id'], 'value', $T['status__name']);
                            ?>

                            <td class="ticket__column__<?= $c_column_id ?> ticket-status--<?= $T['status__state'] ?>"> 
                            <?= $displaystatus ?> 
                            
                            </td>

                        <?php endif ?>
                        
                    <?php break; ?>


                    <?php case 'priority': ?>
                        <?php if( TicketOptionsPlugin::show_all_ticket_columns() || ( !$search || $status ) ): ?>
                        <td class="ticket__column__<?= $c_column_id ?> nohover" align="center"
                            style="background-color:<?php echo $T['cdata__:priority__priority_color']; ?>;">
                            <?php echo $T['cdata__:priority__priority_desc']; ?>
                        </td>

                        <?php endif ?>
                    <?php break; ?>


                    <?php case 'assignee': ?>
                    <?php if( $showassigned ): ?>

                    <td class="ticket__column__<?= $c_column_id ?>">
                        <?php if( $T['staff_id'] ): ?>
                        <?= new AgentsName($T['staff__firstname'].' '.$T['staff__lastname']) ?>

                        <?php elseif( $T['team_id'] ): ?>
                        <?= Team::getLocalById($T['team_id'], 'name', $T['team__name']) ?>
                        
                        <?php endif ?>
                    </td>

                    <?php endif ?>
                    <?php break; ?>


                    <?php case 'dept': ?>
                    <td class="ticket__column__<?= $c_column_id ?>" nowrap>
                        <span class="truncate" style="max-width: 169px">
                        <?= Format::htmlchars( Dept::getLocalById( $T['dept_id'], 'name', $T['dept__name'] ) ) ?>

                        </span>
                    </td>
                    <?php break; ?>

                    <?php endswitch ?>
                <?php endforeach ?>
                

                <!--
                -->
                


            </tr>
            <!-- Replaced by TicketOptionsPlugin -->
            <?php endforeach ?>
            <?php
        if (!$total)
            $ferror=__('There are no tickets matching your criteria.');
        ?>
    </tbody>
    <tfoot>
     <tr>
        <td colspan="8">
            <?php if($total && $thisstaff->canManageTickets()){ ?>
            <?php echo __('Select');?>:&nbsp;
            <a id="selectAll" href="#ckb"><?php echo __('All');?></a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb"><?php echo __('None');?></a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb"><?php echo __('Toggle');?></a>&nbsp;&nbsp;
            <?php }else{
                echo '<i>';
                echo $ferror?Format::htmlchars($ferror):__('Query returned 0 results.');
                echo '</i>';
            } ?>
        </td>
     </tr>
    </tfoot>
    </table>
    <?php
    if ($total>0) { //if we actually had any tickets returned.
?>      <div>
            <span class="faded pull-right"><?php echo $pageNav->showing(); ?></span>
<?php
        echo __('Page').':'.$pageNav->getPageLinks().'&nbsp;';
        echo sprintf('<a class="export-csv no-pjax" href="?%s">%s</a>',
                Http::build_query(array(
                        'a' => 'export', 'h' => $hash,
                        'status' => $_REQUEST['status'])),
                __('Export'));
        echo '&nbsp;<i class="help-tip icon-question-sign" href="#export"></i></div>';
    } ?>
    </form>
</div>

<div style="display:none;" class="dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm');?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="mark_overdue-confirm">
        <?php echo __('Are you sure you want to flag the selected tickets as <font color="red"><b>overdue</b></font>?');?>
    </p>
    <div><?php echo __('Please confirm to continue.');?></div>
    <hr style="margin-top:1em"/>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="button" value="<?php echo __('No, Cancel');?>" class="close">
        </span>
        <span class="buttons pull-right">
            <input type="button" value="<?php echo __('Yes, Do it!');?>" class="confirm">
        </span>
     </p>
    <div class="clear"></div>
</div>
<script type="text/javascript">
$(function() {
    $('[data-toggle=tooltip]').tooltip();
});
</script>
