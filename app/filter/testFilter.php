<?php

namespace OsumiFramework\App\Filter;

/*
 * Test filter
 */
function testFilter($params, $headers) {
	global $c;
	$ret = ['status'=>'error', 'data'=>null];

	$ret['status'] = 'ok';
	$ret['id'] = 33;

	return $ret;
}