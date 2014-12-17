<?
/**
 * proposals.php
 *
 * @author Magnus Rosenbaum <dev@cmr.cx>
 * @package Basisentscheid
 */


require "inc/common.php";

$ngroup = Ngroup::get();

if ($action) {
	switch ($action) {
	case "select_period":
		Login::access_action("admin");
		action_proposal_select_period();
		break;
	default:
		warning(_("Unknown action"));
		redirect();
	}
}


html_head(_("Proposals"), true);

if (Login::$member and Login::$member->entitled($ngroup->id)) {
?>
<div class="add_record"><a href="proposal_edit.php?ngroup=<?=$ngroup->id?>" class="icontextlink"><img src="img/plus.png" width="16" height="16" alt="<?=_("plus")?>"><?=_("Add proposal")?></a></div>
<?
}

$filter = @$_GET['filter'];
$search = trim(@$_GET['search']);

// count issues in each state
$sql = "SELECT state, count(*)
	FROM issue
	JOIN area ON area.id = issue.area AND area.ngroup = ".intval($ngroup->id)."
	GROUP BY state";
$result = DB::query($sql);
$counts = array(
	'admission'   => 0,
	'debate'      => 0,
	'preparation' => 0,
	'voting'      => 0,
	'counting'    => 0,
	'finished'    => 0,
	'cancelled'   => 0
);
while ( $row = DB::fetch_row($result) ) $counts[$row[0]] = $row[1];

$nyvic = $ngroup->not_yet_voted_issues_count();

$filters = array(
	'' => array(
		_("Open"),
		_("issues in admission, debate and voting phases")
	),
	'admission' => array(
		_("Admission")." (".$counts['admission'].")",
		$counts['admission']==1 ? _("1 issue in admission phase") : sprintf(_("%d issues in admission phase"), $counts['admission'])
	),
	'debate' => array(
		_("Debate")." (".$counts['debate'].")",
		$counts['debate']==1 ? _("1 issue in debate phase") : sprintf(_("%d issues in debate phase"), $counts['debate'])
	),
	'voting' => array(
		_("Voting")." (".($counts['voting']+$counts['preparation']+$counts['counting'])
		.($nyvic?(", ".sprintf(_("not voted on %d"), $nyvic)):"").")",
		sprintf(_("%d issues in voting, %d in voting preparation and %d in counting phase"),
			$counts['voting'], $counts['preparation'], $counts['counting'])
		.($nyvic?(" &mdash; ".Ngroup::not_yet_voted($nyvic)):"")
	),
	'closed' => array(
		_("Closed")." (".($counts['finished']+$counts['cancelled']).")",
		sprintf(_("%d issues are finished, %d issues are cancelled"),
			$counts['finished'], $counts['cancelled'])
	)
);

?>
<div class="filter">
<?
foreach ( $filters as $key => $name ) {
	$params = array('ngroup'=>$ngroup->id);
	if ($key)    $params['filter'] = $key;
	if ($search) $params['search'] = $search;
?>
<a href="<?=URI::build($params)?>"<?
	?> title="<?=$name[1]?>"<?
	if ($key==$filter) { ?> class="active"<? }
	?>><?=$name[0]?></a>
<?
}
?>
<form id="search" action="<?=BN?>" method="GET">
<?
input_hidden('ngroup', $ngroup->id);
if ($filter) input_hidden("filter", $filter);
?>
<?=_("Search")?>: <input type="text" name="search" value="<?=h($search)?>">
<input type="submit" value="<?=_("search")?>">
<a href="<?=URI::strip(['search'])?>"><?=_("reset")?></a>
</form>
</div>

<table class="proposals">
<?

$pager = new Pager(10);

$sql = "SELECT issue.*
	FROM issue
	JOIN area ON area.id = issue.area AND area.ngroup = ".intval($ngroup->id);
$where = array();
$order_by = " ORDER BY issue.id DESC";
$show_results = false;

switch (@$_GET['filter']) {
case "admission":
	$where[] = "issue.state='admission'";
	break;
case "debate":
	$where[] = "issue.state='debate'";
	$order_by = " ORDER BY issue.period DESC, issue.id DESC";
	break;
case "voting":
	$where[] = "(issue.state='voting' OR issue.state='preparation' OR issue.state='counting')";
	$order_by = " ORDER BY issue.period DESC, issue.id DESC";
	break;
case "closed":
	$where[] = "(issue.state='finished' OR issue.state='cancelled')";
	$show_results = true;
	break;
default: // open
	$where[] = "(issue.state!='finished' AND issue.state!='cancelled')";
}

if ($search) {
	$pattern = DB::esc("%".strtr($search, array('%'=>'\%', '_'=>'\_'))."%");
	$where[] = "(title ILIKE ".$pattern." OR content ILIKE ".$pattern." OR reason ILIKE ".$pattern.")";
	$sql .= " JOIN proposal ON proposal.issue = issue.id"
		.DB::where_and($where)
		." GROUP BY issue.id";
} else {
	$sql .= DB::where_and($where);
}

$sql .= $order_by;

$result = DB::query($sql);
$pager->seek($result);
$line = $pager->firstline;

// collect issues and proposals
$issues = array();
$proposals_issue = array();
$submitted_issue = array();
$period = 0;
$period_rowspan = array();
$i = 0;
$i_first = 0;
while ( $issue = DB::fetch_object($result, "Issue") and $line <= $pager->lastline ) {
	$issues[] = $issue;
	list($proposals, $submitted) = $issue->proposals_list();
	$proposals_issue[] = $proposals;
	$submitted_issue[] = $submitted;
	// calculate period rowspan
	if ($period and $issue->period == $period) {
		$period_rowspan[$i] = 0;
		$period_rowspan[$i_first] += count($proposals) + 1;
	} else {
		$period_rowspan[$i] = count($proposals);
		$i_first = $i;
		$period = $issue->period;
	}
	$i++;
	$line++;
}

Issue::display_proposals_th($show_results);

// display issues and proposals
foreach ( $issues as $i => $issue ) {
	/** @var $issue Issue */
?>
	<tr><td colspan="<?= $period_rowspan[$i] ? 6 : 5 ?>" class="issue_separator"></td></tr>
<?
	$issue->display_proposals($proposals_issue[$i], $submitted_issue[$i], $period_rowspan[$i], $show_results);
}

?>
</table>

<?
$pager->msg_itemsperpage = _("Issues per page");
$pager->display();


html_foot();
