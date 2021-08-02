<?php

namespace Drupal\food\Core\Location;

class Point {
	
	/**
     * @var double
     */
	public $latitude;

	/**
     * @var double
     */
	public $longitude;
	

	//http://assemblysys.com/php-point-in-polygon-algorithm/
	public function checkPolygonPosition ($polygon, $pointOnVertex = true) {
        $vertices = array(); 
        foreach ($polygon as $vertex) {
            $vertices[] = $vertex;
        }
		//Close the polygon
		$vertices[] = $polygon[0];
 
        // Check if the point sits exactly on a vertex
        if ($pointOnVertex == true and $this->isOnVertex($vertices) == true) {
            return (PointPolygonPosition::Vertex);
        }
 
        // Check if the point is inside the polygon or on the boundary
        $intersections = 0; 
        $vertices_count = count($vertices);
 
        for ($i=1; $i < $vertices_count; $i++) {
            $vertex1 = $vertices[$i-1]; 
            $vertex2 = $vertices[$i];
            if ($vertex1->longitude == $vertex2->longitude and $vertex1->longitude == $this->longitude and $this->latitude > min($vertex1->latitude, $vertex2->latitude) and $this->latitude < max($vertex1->latitude, $vertex2->latitude)) { // Check if point is on an horizontal polygon boundary
                return (PointPolygonPosition::Boundary);
            }
            if ($this->longitude > min($vertex1->longitude, $vertex2->longitude) and $this->longitude <= max($vertex1->longitude, $vertex2->longitude) and $this->latitude <= max($vertex1->latitude, $vertex2->latitude) and $vertex1->longitude != $vertex2->longitude) { 
                $xinters = ($this->longitude - $vertex1->longitude) * ($vertex2->latitude - $vertex1->latitude) / ($vertex2->longitude - $vertex1->longitude) + $vertex1->latitude; 
                if ($xinters == $this->latitude) { // Check if point is on the polygon boundary (other than horizontal)
                    return (PointPolygonPosition::Boundary);
                }
                if ($vertex1->latitude == $vertex2->latitude || $this->latitude <= $xinters) {
                    $intersections++; 
                }
            } 
        } 
        // If the number of edges we passed through is odd, then it's in the polygon. 
        if ($intersections % 2 != 0) {
            return (PointPolygonPosition::Inside);
        } else {
            return (PointPolygonPosition::Outside);
        }
    }
 
    public function isOnVertex($vertices) {
        foreach($vertices as $vertex) {
            if ($this->latitude == $vertex->latitude && $this->longitude == $vertex->longitude) {
                return true;
            }
        }
		
		return (false);
    }
}
