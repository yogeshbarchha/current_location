<?php

namespace Drupal\food\Core\Location;

class PointPolygonPosition extends \Imbibe\Language\EnumBase {
	
	const Outside = 0;
	const Vertex = 1;
	const Boundary = 2;
	const Inside = 3;
	
}
