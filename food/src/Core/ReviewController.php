<?php

namespace Drupal\food\Core;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Imbibe\Util\PhpHelper;

abstract class ReviewController extends ControllerBase {

    public static function getAllQuestions($config = array()) {
    	$query = db_select('review_questions', 'rq')
			->fields('rq');

		$config['defaultSortField'] = 'question_positionID';
		$config['pageSize'] = 0;
		$rows = ControllerBase::executeListQuery($query, $config);
        return($rows);
    }

 public function getAllavtiveQuestions($config = array()) {
         $query = db_select('review_questions', 'rq')
			->fields('rq')
			->condition('status', '1');

		$config['defaultSortField'] = 'question_positionID';
		$config['pageSize'] = 0;
		$rows = ControllerBase::executeListQuery($query, $config);
        return($rows);
    }
    public function getavtiveQuestionsname($question_id) {
        $query = db_select('review_questions', 'rq')
			->fields('rq',array('question_name'))
			->condition('question_id', $question_id);
					
		$row = ControllerBase::executeRowQuery($query);
		return($row);
    }
    
    
    public function getQuestion($question_id) {
		$query = db_select('review_questions', 'rq')
			->fields('rq')
			->condition('question_id', $question_id);
					
		$row = ControllerBase::executeRowQuery($query);
		return($row);
    }

    public function getCurrentUserReview() {
		$query = db_select('user_review', 'ur')
			->fields('ur')
			->condition('user_id', \Drupal::currentUser()->id());
					
		$row = ControllerBase::executeRowQuery($query);
		return($row);
    }

    public function getCurrentUserReviewByOrderId($order_id, $config = array()) {
		$query = db_select('user_review', 'ur')
			->fields('ur')
			->condition('order_id', $order_id)
			->condition('user_id', \Drupal::currentUser()->id())
			->execute()
			->fetchAll();

		return $query;
			
    }

    public function getAverageRatingByOrderId($order_id) {
    	$output = array('average_rating' => 0,'rating_number' => 0);
    	$rating_number = 0;
    	$total_points = 0;
		// $query = db_query('SELECT rating_number, FORMAT((total_points / rating_number),1) as average_rating FROM user_review WHERE order_id = :order_id',array(':order_id' => $order_id))->fetchAssoc();

		$query = db_select('user_review', 'ur')
			->fields('ur')
			->condition('order_id', $order_id)
			->execute()
			->fetchAll();
					

		if(!empty($query)){
			foreach ($query as $key => $value) {
				$total_points += $value->total_points;
				$rating_number += $value->rating_number;
			}

			$output['average_rating'] =	number_format($total_points / $rating_number,1);
			$output['rating_number'] = $rating_number;
		}					
		return $output;
    }

    public static function getAllUserReview($config = array()) {
    	$query = db_select('user_review', 'ur')
			->fields('ur');

		$conditionCallback = PhpHelper::getNestedValue($config,['conditionCallback']);
    	if ($conditionCallback != NULL) {
      		$query = call_user_func_array($conditionCallback, [$query]);
    	}
		$config['defaultSortField'] = ['created','DESC'];
		$config['pageSize'] = 10;
		$rows = ControllerBase::executeListQuery($query, $config);
        return($rows);	
    }

    public static function getUserReviewById($review_id, $config = array()) {
    	$query = db_select('user_review', 'ur')
    	->condition('rating_id', $review_id)
			->fields('ur');

		$conditionCallback = PhpHelper::getNestedValue($config,['conditionCallback']);
    	if ($conditionCallback != NULL) {
      		$query = call_user_func_array($conditionCallback, [$query]);
    	}
		$config['defaultSortField'] = ['created','DESC'];
		$config['pageSize'] = 0;
		$rows = ControllerBase::executeListQuery($query, $config);
        return($rows);	
    }

    public static function getReviewByRestaurant($restaurant_id) {
    	if(empty($restaurant_id)){
    		return;
    	}
		
		$query = db_select('user_review','ur');
		$query->join('food_order', 'fo', 'ur.order_id = fo.order_id');
		$query->join('users_field_data', 'u', 'ur.user_id = u.uid');
		$result = $query->fields('ur')
			->fields('u',array('name','mail','status'))
			->condition('fo.restaurant_id', $restaurant_id,'=')
			->condition('ur.status', 1)
			->execute()
			->fetchAll();

		return $result;
    }
    
}
