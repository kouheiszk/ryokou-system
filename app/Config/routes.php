<?php
	Router::parseExtensions('json');
	Router::connect('/', array('controller' => 'tools', 'action' => 'calc_stb'));
	Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));
	CakePlugin::routes();
	require CAKE . 'Config' . DS . 'routes.php';
