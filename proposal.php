<?
/**
 * proposal.php
 *
 * @author Magnus Rosenbaum <dev@cmr.cx>
 * @package Basisentscheid
 */


require "inc/common.php";

$proposal = new Proposal(@$_GET['id']);
if (!$proposal->id) {
	error("The requested proposal does not exist!");
}

$issue = $proposal->issue();

if (Login::$member) $edit_limit = strtotime("- ".ARGUMENT_EDIT_INTERVAL);

if ($action) {
	switch ($action) {
	case "add_support":
		Login::access_action("member");
		if ($proposal->state=="submitted") {
			$proposal->add_support();
		} else {
			warning("Support for this proposal can not be added, because it is not in the submitted phase!");
		}
		redirect();
		break;
	case "revoke_support":
		Login::access_action("member");
		if ($proposal->state=="submitted") {
			$proposal->revoke_support();
		} else {
			warning("Support for this proposal can not be removed, because it is not in the submitted phase!");
		}
		redirect();
		break;
	case "demand_offline":
		Login::access_action("member");
		if ($proposal->state=="submitted" or $proposal->state=="admitted" or $issue->state=="debate") {
			$issue->demand_secret();
		} else {
			warning("Support for secret voting can not be added, because the proposal is not in submitted, admitted or debate phase!");
		}
		redirect();
		break;
	case "revoke_demand_offline":
		Login::access_action("member");
		if ($proposal->state=="submitted" or $proposal->state=="admitted" or $issue->state=="debate") {
			$issue->revoke_secret();
		} else {
			warning("Support for secret voting can not be removed, because the proposal is not in submitted, admitted or debate phase!");
		}
		redirect();
		break;
	case "select_period":
		Login::access_action("admin");
		action_proposal_select_period();
		break;
	case "add_argument":
		Login::access_action("member");
		action_required_parameters("title", "content", "parent");
		$argument = new Argument;
		if ($_POST['parent']=="pro" or $_POST['parent']=="contra") {
			$argument->parent = 0;
			$argument->side = $_POST['parent'];
		} else {
			$parent = new Argument($_POST['parent']);
			if (!$parent->id) {
				warning("Parent argument does not exist.");
				redirect();
			}
			$argument->parent = $parent->id;
			$argument->side = $parent->side;
		}
		$argument->proposal = $proposal->id;
		$argument->member = Login::$member->id;
		$argument->title = trim($_POST['title']);
		if (!$argument->title) {
			warning("The title of the argument must be not empty.");
			break;
		}
		$argument->content = trim($_POST['content']);
		if (!$argument->content) {
			warning("The content of the argument must be not empty.");
			break;
		}
		$argument->create();
		redirect(URI::strip(array("argument_parent"))."#argument".$argument->id);
		break;
	case "update_argument":
		Login::access_action("member");
		action_required_parameters("title", "content", "id");
		$argument = new Argument($_POST['id']);
		if (!$argument->id) {
			warning("This argument does not exist.");
			redirect();
		}
		if ($argument->member!=Login::$member->id) {
			warning("You are not the author of the argument.");
			redirect();
		}
		if (strtotime($argument->created) < $edit_limit) {
			warning("This argument may not be updated any longer.");
			redirect();
		}
		$argument->title = trim($_POST['title']);
		if (!$argument->title) {
			warning("The title of the argument must be not empty.");
			break;
		}
		$argument->content = trim($_POST['content']);
		if (!$argument->content) {
			warning("The content of the argument must be not empty.");
			break;
		}
		$argument->update(array("title", "content"), "updated=now()");
		redirect(URI::strip(array("argument_edit"))."#argument".$argument->id);
		break;
	case "rating_plus":
	case "rating_minus":
		Login::access_action("member");
		action_required_parameters("argument");
		$argument = new Argument($_POST['argument']);
		if ($argument->member==Login::$member->id) {
			warning("Rating your own arguments is not allowed.");
			redirect();
		}
		$argument->set_rating($action=="rating_plus");
		redirect(URI::$uri."#argument".$argument->id);
		break;
	case "rating_reset":
		Login::access_action("member");
		action_required_parameters("argument");
		$argument = new Argument($_POST['argument']);
		$argument->delete_rating();
		redirect(URI::$uri."#argument".$argument->id);
		break;
	default:
		warning("Unknown action");
		redirect();
	}
}


html_head(_("Proposal")." ".$proposal->id);

?>


<div style="float:right; margin-left:20px; width:20%">
<h2><?=_("Area")?></h2>
<p class="proposal"><?=h($issue->area()->name)?></p>
<h2><?=_("Proponents")?></h2>
<p class="proposal"><?=content2html($proposal->proponents)?></p>
</div>

<div style="overflow:hidden">
<!--<div style="float:right"><a href="proposal_edit.php?id=<?=$proposal->id?>"><?=_("Edit proposal")?></a></div>-->
<h2><?=_("Title")?></h2>
<p class="proposal proposal_title"><?=h($proposal->title)?></p>
<h2><?=_("Content")?></h2>
<p class="proposal"><?=content2html($proposal->content)?></p>
<h2><?=_("Reason")?></h2>
<p class="proposal"><?=content2html($proposal->reason)?></p>
</div>

<br style="clear:both">

<div>
	<div class="arguments_side" style="float:left">
<?
if (Login::$member and @$_GET['argument_parent']!="pro") {
?>
		<div style="float:right"><a href="<?=URI::append(array('argument_parent'=>"pro"))?>#form"><?=_("Add new pro argument")?></a></div>
<?
}
?>
		<h2><?=_("Pro")?></h2>
		<? arguments("pro", "pro"); ?>
	</div>
	<div class="arguments_side" style="float:right">
<?
if (Login::$member and @$_GET['argument_parent']!="contra") {
?>
		<div style="float:right"><a href="<?=URI::append(array('argument_parent'=>"contra"))?>#form"><?=_("Add new contra argument")?></a></div>
<?
}
?>
		<h2><?=_("Contra")?></h2>
		<? arguments("contra", "contra"); ?>
	</div>
	<div style="clear:both"></div>
</div>

<div class="quorum">
<div style="float:left; margin-right:10px">
<?
$proposal->bargraph_quorum();
?>
</div>
<b><?=_("Supporters")?>:</b> <?
$supported_by_member = $proposal->show_supporters();
if (Login::$member and $proposal->state=="submitted") {
?>
<br clear="both">
<?
	if ($supported_by_member) {
		form(URI::$uri, 'style="background-color:green; display:inline-block"');
?>
&#10003; <?=_("You support this proposal.")?>
<input type="hidden" name="action" value="revoke_support">
<input type="submit" value="<?=_("Revoke your support for this proposal")?>">
</form>
<?
	} else {
		form(URI::$uri, 'style="display:inline-block"');
?>
<input type="hidden" name="action" value="add_support">
<input type="submit" value="<?=_("Support this proposal")?>">
</form>
<?
	}
}
?>
</div>

<div class="quorum">
<div style="float:left; margin-right:10px">
<?
$issue->bargraph_secret();
?>
</div>
<b><?=_("Secret voting demanders")?>:</b> <?
$demanded_by_member = $issue->show_offline_demanders();
if (Login::$member and ($proposal->state=="submitted" or $proposal->state=="admitted" or $issue->state=="debate")) {
?>
<br clear="both">
<?
	if ($demanded_by_member) {
		form(URI::$uri, 'style="background-color:red; display:inline-block"');
?>
&#10003; <?=_("You demand secret voting for this issue.")?>
<input type="hidden" name="action" value="revoke_demand_offline">
<input type="submit" value="<?=_("Revoke your demand for secret voting")?>">
</form>
<?
	} else {
		form(URI::$uri, 'style="display:inline-block"');
?>
<input type="hidden" name="action" value="demand_offline">
<input type="submit" value="<?=_("Demand secret voting for this issue")?>">
</form>
<?
	}
}
?>
</div>

<div style="margin-top:20px">
<?
if (Login::$member) {
?>
<div style="float:right"><a href="proposal_edit.php?issue=<?=$proposal->issue?>"><?=_("Add alternative proposal")?></a></div>
<?
}
?>
<h2><?=_("This and alternative proposals")?></h2>
<table border="0" cellspacing="1" class="proposals">
<?
Issue::display_proposals_th();
$proposals = $issue->proposals_list();
$issue->display_proposals($proposals, count($proposals), $proposal->id);
?>
</table>
</div>

<?

html_foot();


/**
 * list the sub-arguments for one parent-argument
 *
 * @param string  $side   "pro" or "contra"
 * @param mixed   $parent ID of parent argument or "pro" or "contra"
 */
function arguments($side, $parent) {
	global $proposal, $edit_limit;

	$sql = "SELECT arguments.*, (arguments.plus - arguments.minus) AS rating";
	if (Login::$member) {
		$sql .= ", ratings.positive
			FROM arguments
			LEFT JOIN ratings ON ratings.argument = arguments.id AND ratings.member = ".intval(Login::$member->id);
	} else {
		$sql .= "
			FROM arguments";
	}
	// intval($parent) gives parent=0 for "pro" and "contra"
	$sql .= "	WHERE proposal=".intval($proposal->id)."
			AND side=".m($side)."
			AND parent=".intval($parent)."
		ORDER BY rating DESC, arguments.created";
	$result = DB::query($sql);
	if (!pg_num_rows($result) and @$_GET['argument_parent']!=$parent) return;

?>
<ul>
<?

	while ( $argument = DB::fetch_object($result, "Argument") ) {
		if (Login::$member) DB::pg2bool($argument->positive);
		$member = new Member($argument->member);
?>
	<li>
<?

		// author and form
		if (Login::$member and $member->id==Login::$member->id and @$_GET['argument_edit']==$argument->id) {
?>
		<div class="author"><?=$member->username()?> <?=datetimeformat($argument->created)?></div>
<?
			if (strtotime($argument->created) > $edit_limit) {
?>
		<div class="time"><?=strtr(_("This argument can be updated until %datetime%."), array('%datetime%'=>datetimeformat($argument->created." + ".ARGUMENT_EDIT_INTERVAL)))?></div>
<?
				form(URI::$uri, 'class="argument"');
?>
<a name="argument<?=$argument->id?>"></a>
<input name="title" type="text" value="<?=h(!empty($_POST['title'])?$_POST['title']:$argument->title)?>"><br>
<textarea name="content" rows="5"><?=h(!empty($_POST['content'])?$_POST['content']:$argument->content)?></textarea><br>
<input type="hidden" name="action" value="update_argument">
<input type="hidden" name="id" value="<?=$argument->id?>">
<input type="submit" value="<?=_("apply changes")?>">
</form>
<?
				$display_content = false;
			} else {
?>
		<div class="time"><?=_("This argument may not be updated any longer!")?></div>
<?
				$display_content = true;
			}
		} else {
?>
		<div class="author"><?
			if (Login::$member and $member->id==Login::$member->id and strtotime($argument->created) > $edit_limit) {
				?><a href="<?=URI::append(array('argument_edit'=>$argument->id))?>#argument<?=$argument->id?>"><?=_("edit")?></a> <?
			}
			?><?=$member->username()?> <?=datetimeformat($argument->created)?></div>
<?
			$display_content = true;
		}

		// title and content
		if ($display_content) {
			if ($argument->updated) {
?>
		<div class="author"><?=_("updated")?> <?=datetimeformat($argument->updated)?></div>
<?
			}
?>
		<h3><a class="anchor" name="argument<?=$argument->id?>"></a><?=h($argument->title)?></h3>
		<p><?=content2html($argument->content)?></p>
<?
		}

		// rating and reply
		if (Login::$member and @$_GET['argument_parent']!=$argument->id) {
?>
		<div class="reply"><a href="<?=URI::append(array('argument_parent'=>$argument->id))?>#form"><?=_("Reply")?></a></div>
<?
		}
		if ($argument->plus) {
			?><span class="plus<? if (Login::$member and $argument->positive===true) { ?> me<? } ?>">+<?=$argument->plus?></span> <?
		}
		if ($argument->minus) {
			?><span class="minus<? if (Login::$member and $argument->positive===false) { ?> me<? } ?>">-<?=$argument->minus?></span> <?
		}
		if ($argument->plus and $argument->minus) {
			?><span class="rating">=<?=$argument->rating?></span> <?
		}
		if (Login::$member and $argument->member!=Login::$member->id) { // don't allow to rate ones own arguments
			if ($argument->positive!==null) {
				form(URI::$uri, 'class="button"');
?>
<input type="hidden" name="argument" value="<?=$argument->id?>">
<input type="hidden" name="action" value="rating_reset">
<input type="submit" value="<?=_("reset")?>">
</form>
<?
			} else {
				form(URI::$uri, 'class="button"');
?>
<input type="hidden" name="argument" value="<?=$argument->id?>">
<input type="hidden" name="action" value="rating_plus">
<input type="submit" value="+1">
</form>
<?
				form(URI::$uri, 'class="button"');
?>
<input type="hidden" name="argument" value="<?=$argument->id?>">
<input type="hidden" name="action" value="rating_minus">
<input type="submit" value="-1">
</form>
<?
			}
		}

?>
		<div class="clearfix"></div>
<?
		arguments($side, $argument->id);
?>
	</li>
<?
	}

	if (Login::$member and @$_GET['argument_parent']==$parent) {
?>
	<li>
<?
		form(URI::$uri, 'class="argument"');
?>
<a name="form"></a>
<div class="time"><?=_("New argument")?></div>
<input name="title" type="text" value="<?=h(@$_POST['title'])?>"><br>
<textarea name="content" rows="5"><?=h(@$_POST['content'])?></textarea><br>
<input type="hidden" name="action" value="add_argument">
<input type="hidden" name="parent" value="<?=$parent?>">
<input type="submit" value="<?=_("save")?>">
</form>
	</li>
<?
	}

?>
</ul>
<?
}
