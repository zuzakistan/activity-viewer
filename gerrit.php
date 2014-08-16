<?php
$gEndpoint = 'https://gerrit.wikimedia.org';
$gEmails = [
	'douglas@chippy.ch',
	'danharibo+wt@gmail.com',
];

$qString = '';
foreach ( $gEmails as $email ) {
	$qString .= 'owner:' . $email . ' OR ';
}
$qString = substr( $qString, 0, strlen( $qString ) - 4 );
$qString = $gEndpoint . '/r/changes/?q=' . urlencode( $qString );

$res = get( $qString );
/*echo '<table class="table">';
echo construct_row( [
	array( '<abbr title="Wikimedia Foundation">WMF</abbr>', 'text-info text-center status' ),
	'Project',
	'Summary',
	'User',
	'<abbr title="Code-Review">CR</abbr>',
	'<abbr title="Verified">V</abbr>',
], 'h' );*/
foreach( $res as $change ) {
	echo parse_gerrit( get_gerrit( $change->change_id ) );
}
//echo "</table>";



function get_gerrit( $id ) {
	global $gEndpoint;
	$url = $gEndpoint . "/r/changes/$id/detail";
	return get( $url );
}
function get( $url ) {
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_USERAGENT, 'GerritViewer/1.0 [[User:Microchip08]] MC8@freenode files.chippy.ch/gerrit' );
	$result = curl_exec( $ch );
	curl_close( $ch );

	$result = substr( $result, 4 ); // gerrit prepends )]}' to responses
	return json_decode( $result );
}

function parse_gerrit( $data ) {
	global $gEndpoint;
	if ( !is_object( $data ) ) {
		return false;
	}
	$icon = "";
	$class = 'status text-center ';
	$title = $data->status;
	$aband = false;
	if ( $data->status == 'MERGED' ) {
		$icon = 'fa-smile-o text-success';
		$class .= "success";
		$title = 'Change has been successfully merged into the repository.';
	} else if ( $data->status == 'ABANDONED' ) {
		$icon = 'fa-trash-o text-danger';
		$class .= "danger";
		$title = 'Change has been abandoned.';
		$aband = true;
	} else if ( $data->status == 'DRAFT' ) {
		$icon = 'fa-file';
		$title = 'Change is a draft.';
	} else if ( calc_state( $data, 'Code-Review', $aband, true ) < 0 ) {
		$icon = 'fa-warning text-warning';
		$class .= "warning";
		$title = 'There is a problem with this change that needs fixing.';
	} else {
		$icon = 'fa-comments-o text-info';
		$title = 'Change is under discussion.';
		$class .= 'info';
	}
	$columns = [
		array( "<i title=\"" . $title . "\" class=\"fa $icon fa-fw text-center\"></i><span class=\"fallback\">" . $data->status . '</span>', $class ),
		get_project( $data->project ),
		'<a href="' . $gEndpoint . '/r/' . $data->_number . '">' . $data->subject . '</a>',
		'<a href="' . $gEndpoint . '/r/q/owner:' . urlencode( $data->owner->name ) . ',n,z">' .
		'<img class="gravatar" src="' . get_gravatar( $data->owner->email ) . '?s=25&d=blank" title="' . $data->owner->name . '"/>' . //$data->owner->name .
		'</a>',

		calc_state( $data, 'Code-Review', $aband ),
		calc_state( $data, 'Verified', $aband ),
	];
	// classes:
	return construct_row( $columns, 'd' ); //$class );
}
function construct_row( $row, $type = 'd', $class = '' ) {
	if ( !is_array( $row ) ) {
		return false;
	}
	if ( $class ) {
		$str = "<tr class=\"$class\">";
	} else {
		$str = "<tr>";
	}
	foreach( $row as $datum ) {
		if ( is_array( $datum ) ) {
			if ( count( $datum ) > 1 ) {
				$str .= "<t$type class=\"" . $datum[1] . '">' . $datum[0] . "</t$type>";
			} else {
				$str .= "<t$type>" . $datum[0] . "</t$type>";
			}
		} else {
			$str .= "<t$type>$datum</t$type>";
		}
	}
	$str .= "</tr>";
	return $str;
}
function calc_state( $data, $status, $aband, $raw = false ) {
	$labels = $data->labels->{$status};
	$states = [
		['rejected', 'fa-times text-danger','-2'],
		['approved','fa-check text-success','+2'],
		['disliked', 'fa-thumbs-down text-danger','-1'],
		['recommended', 'fa-thumbs-up text-success','+1'],
	];
	$ret = ['neutral', 'fa-circle-o text-info', '0'];
	foreach ( $states as $state ) {
		if ( property_exists( $labels, $state[0] ) ) {
			$ret = $state;
			break;
		}
	}
	if ( $raw ) {
		return $ret[2];
	}
	if ( property_exists( $labels->values, $ret[2] ) ) {
		$caption = $labels->values->{$ret[2]};
	} else {
		$caption = "(No score)";
	}
	if ( $ret[0] == 'neutral' && $aband ) {
		$ret[1] = 'fa-minus text-muted';
		$caption = "(No score: irrelevant)";
	}
	$response = '<span title="' . $caption;
	$response .= '" class="' . $ret[0];
	$response .= '"><i class="fa fa-fw ' . $ret[1];
	$response .= '"></i>&nbsp;</span>';
	$respose .= '<span class="fallback">' . $ret[0] . '</span>';
	return $response;
}
function get_gravatar( $email ) {
	if ( $email == 'danharibo+wt@gmail.com' ) {
		$email = 'danharibo@gmail.com';
	}
	$hash = md5( strtolower( trim( $email ) ) );
	return "//www.gravatar.com/avatar/$hash.jpg";
}
function get_project( $str ) {
	$project = explode( '/', $str );
	if ( $project[0] == 'mediawiki' ) {
		if ( $project[1] == 'extensions' ) {
			$url = '//mediawiki.org/wiki/Extension:' . $project[2];
		} else if ( $project[1] == 'core' ) {
			$url = '//mediawiki.org';
		}
	}
	if ( !isset( $url ) ) {
		$url = "https://gerrit.wikimedia.org/r/#/projects/$str,dashboards/default";
	}
	return "<a href=\"$url\">$str</a>";
}

