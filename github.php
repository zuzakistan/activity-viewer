<?php
$gEndpoint = 'https://api.github.com';
$credentials = parse_ini_file( 'credentials.ini' );
$gCredentials = 'client_id=' . $credentials->github->id;
$gCredentials .= '&client_secret=' . $credentials->github->secret;

$gRepos = array(
	'samtools/samtools',
	'wtsi-hgi/seq_autoqc'
);

$gUsers = array(
	'SamStudio8',
	'danharibo',
	'zuzak'
);

echo '<table class="table">';
echo construct_row( [
		array( '<abbr title="Github" class="fa fa-fw fa-github-alt"></abbr>', 'status text-info text-center' ),
		'Repo',
		'Summary',
		'User',
		'Build'
	], 'h' );


foreach ( $gRepos as $repo ) {
	$data = get( "$gEndpoint/repos/$repo/pulls?state=all&$gCredentials" );
	foreach ( $data as $datum ) {
		if ( in_array( $datum->user->login, $gUsers ) ) {
			$icon = '';
			$class = 'text-center status ';
			$title = $datum->state;

			if ( $title == 'open' ) {
				$icon = 'fa-comments-o text-info';
				$class .= 'info';
				$title = 'Pull request is under discussion.';
			} else if ( $datum->merged_at ) {
				$icon = 'fa-smile-o text-success';
				$title = 'Pull request has been successfully merged into the repository.';
				$class .= 'success';
			} else if ( $title == 'closed' ) {
				$icon = 'fa-times text-danger';
				$class .= 'danger';
				$title = 'Pull request has been closed without a merge.';
			} else {
				$icon = 'fa-circle-o text-muted';
				$class .= 'muted';
				$title = "Pull request is $title.";
			}

			$state = array( "<i title=\"$title\" class=\"fa $icon fa-fw text-center\"></i>", $class );

			$statuses = get( $datum->statuses_url . '?' . $gCredentials );
			$status = array_shift( $statuses );

			$title = $status->description;
			$class = 'build ';

			if ( $status->state == 'pending' ) {
				$icon = 'fa-gears';
				$class .= 'text-info';
			} else if ( $status->state == 'failure' ) {
				$icon = 'fa-thumbs-down';
				$class .= 'text-danger';
			} else if ( $status->state == 'success' ) {
				$icon = 'fa-thumbs-up';
				$text .= 'text-success';
			} else if ( $status->state == 'error' ) {
				$icon = 'fa-exclamation';
				$class .= 'text-warning';
			} else {
				$icon = 'fa-circle-o';
				$class .= 'text-muted';
				if ( !$status->state ) {
					$status->state = 'untested';
				}
			}

			$verified = array( "<span title=\"$title\"><i class=\"fa fa-fw $icon\"></i>" . $status->state . '</span>', $class );

			$columns = [
				$state,
				'<a href="' . $datum->base->repo->html_url . '">' . $datum->base->repo->full_name . '</a>',
				'<a href="' . $datum->html_url . '">' . $datum->title . '</a>',
				'<a href="' . $datum->user->html_url . '">' .
				'<img src="//www.gravatar.com/avatar/' . $datum->user->gravatar_id. '.jpg?s=25&d=blank" />&nbsp;' .
				$datum->user->login . '</a>',
				$verified,
			];

			echo construct_row( $columns, 'd' );
		}
	}
}
echo "</table>";
function get( $url ) {
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_USERAGENT, 'GerritViewer/1.0 zuzak@github MC8@freenode files.chippy.ch/gerrit' );
	$result = curl_exec( $ch );
	curl_close( $ch );

	return json_decode( $result );
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
