<?php
$url = "https://bitbucket.org/api/1.0/repositories/danharibo/openrw/events/";

$ch = curl_init();
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
curl_setopt( $ch, CURLOPT_URL, $url );
$res = curl_exec( $ch );
curl_close( $ch );

$data = json_decode( $res );
$data = $data->events;
$data = $data[0]->repository;
echo '<a href="https://bitbucket.org/danharibo/openrw/src">';
echo $data->{'name'};
echo '</a>';
echo ' was last updated ';
echo date('F j, Y',strtotime($data->last_updated));
echo ' (' . formatBytes( $data->size ) . ').';
function formatBytes($size, $precision = 2)
{ // http://stackoverflow.com/a/2510540
    $base = log($size) / log(1024);
    $suffixes = array('', 'k', 'M', 'G', 'T');

    return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
}
