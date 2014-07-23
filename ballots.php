<?
/**
 *
 * @author Magnus Rosenbaum <dev@cmr.cx>
 * @package Basisentscheid
 */


require "inc/common.php";


$period = new Period(@$_GET['period']);
if (!$period) {
	error("The requested period does not exist!");
}


if ($action) {
	if (!$member) {
		warning("Access denied.");
		redirect();
	}
	if ( empty($_POST['ballot']) ) {
		warning("Parameter missing.");
		redirect();
	}
	$ballot = new Ballot($_POST['ballot']);
	if (!$ballot->id) {
		warning("The requested area does not exist!");
		redirect();
	}
	switch ($action) {
	case "subscribe":
		$ballot->subscribe();
		redirect();
		break;
	case "unsubscribe":
		$ballot->unsubscribe();
		redirect();
		break;
	default:
		warning("Unknown action");
		redirect();
	}
}


html_head(_("Ballots"));

?>

<table border="0" cellspacing="1" cellpadding="2" class="proposals">
	<tr>
		<th><?=_("No.")?></th>
		<th><?=_("Name")?></th>
		<th><?=_("Approved")?></th>
		<th><?=_("Opening")?></th>
		<th><?=_("Voters")?></th>
	</tr>
<?


$pager = new Pager;

$sql = "SELECT ballots.*, voters.member
	FROM ballots
	LEFT JOIN voters ON ballots.id = voters.ballot AND voters.member = ".intval($member->id)."
	WHERE period=".DB::m($period->id)."
	ORDER BY ballots.id";

$result = DB::query($sql);
$pager->seek($result);
while ($row = pg_fetch_assoc($result)) {
	$ballot = new Ballot($row);
?>
	<tr>
		<td><?=$ballot->id?></td>
		<td><?=h($ballot->name)?></td>
		<td><?=boolean($ballot->approved)?></td>
		<td><?=timeformat($ballot->opening)?></td>
		<td><?
	echo $ballot->voters;
	if ($member) {
		if ($row['member']) {
?>
				&#10003;
				<form action="<?=$_SERVER['REQUEST_URI']?>" method="POST" class="button">
				<input type="hidden" name="ballot" value="<?=$ballot->id?>">
				<input type="hidden" name="action" value="unsubscribe">
				<input type="submit" value="<?=_("unsubscribe")?>">
				</form>
				<?
		} else {
?>
				<form action="<?=$_SERVER['REQUEST_URI']?>" method="POST" class="button">
				<input type="hidden" name="ballot" value="<?=$ballot->id?>">
				<input type="hidden" name="action" value="subscribe">
				<input type="submit" value="<?=_("subscribe")?>">
				</form>
				<?
		}
	}
	?></td>
	</tr>
<?
}

?>
</table>

<?
$params = array();
/*if ($filter) $params['filter'] = $filter;
if ($search) $params['search'] = $search;*/
$pager->output(uri($params));
?>

<a href="ballot_edit.php?period=<?=$period->id?>"><?=_("Apply to operate a ballot")?></a>
<?


html_foot();
