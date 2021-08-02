<?php

namespace Drupal\food\Form\Review;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Imbibe\Util\PhpHelper;
use Drupal\Core\Controller\ControllerBase;


class ReviewController extends ControllerBase {
	
	public static function questionManage(){

		$header = array(
	      // We make it sortable by name.
	      array('data' => t('Count')),
	      array('data' => t('Question'), 'field' => 'question_name', 'sort' => 'asc'),
	      array('data' => t('Status'), 'field' => 'status'),
	      array('data' => t('Position'), 'field' => 'question_positionID', 'sort' => 'asc'),
	      array('data' => t('')),
	      array('data' => t('')),
	    );

	    $rows = array();
	    $count = 1;

	    $result = \Drupal\food\Core\ReviewController::getAllQuestions();

	    if(!empty($result)){
	    	
	    	foreach ($result as $key => $value) {
	    		$editUrl = Url::fromRoute('food.order.review.question.edit',['question_id' => $value->question_id]);
	    		$editUrl->setOptions([
				    'attributes' => [
				      'class' => ['use-ajax'],
				      'data-dialog-type' => 'modal',
				      'data-dialog-options' => Json::encode([
				        'width' => 700,
				      ]),
				      'role' => 'button',
				    ],
				]);
	    		$edit_link = Link::fromTextAndUrl(t('Edit'), $editUrl);

	    		$deleteUrl = Url::fromRoute('food.order.review.question.delete',['question_id' => $value->question_id]);
	    		$deleteUrl->setOptions([
				    'attributes' => [
				      'class' => ['use-ajax'],
				      'data-dialog-type' => 'modal',
				      'data-dialog-options' => Json::encode([
				        'width' => 700,
				      ]),
				      'role' => 'button',
				    ],
				]);
	    		$delete_link = Link::fromTextAndUrl(t('Delete'), $deleteUrl);

	    		$rows[] = array('data' => array(
			        'count' => $count,
			        'question' => $value->question_name, // This hardcoded [BLOB] is just for display purpose only.
			        'status' => $value->status ? 'Active' : 'Deactive',
			        'question_positionID' => $value->question_positionID,
			        'edit' => $edit_link,
			        'delete' => $delete_link,
			    ));
			    $count++;
	    	}
	    }

	    // The table description.
	   // $build = array(
	   //   '#markup' => t('<h2>Manage Question</h2>')
	   // );

	    $url = Url::fromRoute('food.order.review.question.add');
	    $url->setOptions([
		    'attributes' => [
		      'class' => ['use-ajax'],
		      'data-dialog-type' => 'modal',
		      'data-dialog-options' => Json::encode([
		        'width' => 700,
		      ]),
		      'role' => 'button',
		    ],
		]);


		$link = [
		  '#type' => 'link',
		  '#url' => $url,
		  '#title' => t('<i class="glyphicon glyphicon-plus"></i> Add Question'),
		  '#attributes' => array('class' => array('btn btn-primary')),
		];


		//$build['add_question_link'] = $link;
	 
	    // Generate the table.
	    $build['review_question_manage_table'] = array(
	      '#theme' => 'table',
	      '#header' => $header,
	      '#rows' => $rows,
	      '#empty' => t('No question found'),
	      '#prefix' => '<div id="review-question-manage-table"><h2>Manage Question</h2><p>'.render($link).'</p>',
          '#suffix' => '</div>',
	    );
	 
	    // Finally add the pager.
	    $build['pager'] = array(
	      '#type' => 'pager'
	    );
	 
	    return $build;
	}


	public static function reviewManage(){

		$header = array(
	      // We make it sortable by name.
	      array('data' => t('Count')),
	      array('data' => t('Order Id'), 'field' => 'order_id', 'sort' => 'asc'),
	      array('data' => t('User Id'), 'field' => 'user_id', 'sort' => 'asc'),
	      array('data' => t('Rating')),
	      array('data' => t('Comment')),
	      array('data' => t('Status'), 'field' => 'status', 'sort' => 'asc'),
	     
	      array('data' => t('')),
	       array('data' => t('Delete'),'field' => 'delete',),
	    );

	    $rows = array();
		$count = 1;
		$result = \Drupal\food\Core\ReviewController::getAllUserReview([
			'header' => $header,
			'pageSize' => 10,
			'conditionCallback' => function($query) {
				$query = $query->orderBy('created','DESC');

                return($query);
            }
        ]);
		

		if(!empty($result)){

			foreach ($result as $key => $value) {
				$review_detail = json_decode($value->review_details);
				$user = user_load($value->user_id);
				$star = '';
				if(!empty($value->total_points)){
					for ($i = 0; $i < $value->total_points; $i++) {					
						$star .= '<img src="/themes/food_theme/images/star.png">';
					}
				}

				$statusUpdateUrl = Url::fromRoute('food.order.review.status',['review_id' => $value->rating_id]);
	    		$statusUpdateUrl->setOptions([
				    'attributes' => [
				      'class' => ['use-ajax'],
				      'data-dialog-type' => 'modal',
				      'data-dialog-options' => Json::encode([
				        'width' => 700,
				      ]),
				      'role' => 'button',
				    ],
				]);

				if($value->status == \Drupal\food\Core\Review\ReviewStatus::Submitted){
					$link_text = t('Approve');
				}elseif($value->status == \Drupal\food\Core\Review\ReviewStatus::Approved){
					$link_text = t('Disapprove');
				}elseif($value->status == \Drupal\food\Core\Review\ReviewStatus::Disapproved){
					$link_text = t('Approve');
				}
	    		
	    		$statusUpdateUrl = Link::fromTextAndUrl($link_text, $statusUpdateUrl);
	    		
	    		$OrderUrl = Url::fromRoute('food.partner.order.detail', array('order_id' => $value->order_id));
                $OrderUrl->setOptions([
                'attributes' => [
                    'target' => '_blank'
                ]
                ]);
             $deleteUrl = Url::fromRoute('food.order.review.detail.delete',['review_id' => $value->rating_id]);
	    		$deleteUrl->setOptions([
				    'attributes' => [
				      'class' => ['use-ajax'],
				      'data-dialog-type' => 'modal',
				      'data-dialog-options' => Json::encode([
				        'width' => 700,
				      ]),
				      'role' => 'button',
				    ],
				]);
                        
                         $query = db_select('users_field_data', 'n')
          ->fields('n', array('name'))
           ->condition('uid', $value->user_id)
          ->execute()
          ->fetchField();
            
	    		$delete_link = Link::fromTextAndUrl(t('Delete'), $deleteUrl);
                $OrderUrlLink = Link::fromTextAndUrl(t('View Order'), $OrderUrl);

				$rows[] = array('data' => array(
			        'count' => array(
			          'data' => array(
			            '#type' => 'html_tag',
			            '#tag' => 'p',
			            '#value' => $value->status == \Drupal\food\Core\Review\ReviewStatus::Submitted ? $count.' <span class="label label-warning">New</span>' : $count,
			          ),
			        ),
			        'order_id' => $OrderUrlLink, // This hardcoded [BLOB] is just for display purpose only.
			        'Name' =>$query,
			        'rating' => array(
			          'data' => array(
			            '#type' => 'html_tag',
			            '#tag' => 'p',
			            '#value' => $star,
			          ),
			        ),
			        'comment' => isset($review_detail->comment) ? $review_detail->comment : '',
			        'status' => $value->status == 1 ? 'Approved' : 'Disapproved',
			        'approve' => $statusUpdateUrl,
			        'delete' => $delete_link
			    ));

			    $count++;
			}
		}

		$build['review_manage_table'] = array(
	      '#theme' => 'table',
	      '#header' => $header,
	      '#rows' => $rows,
	      '#empty' => t('No Review found'),
	         '#prefix' => '<div id="review-question-manage-table"><h2>Manage Review</h2><p></p>',
          '#suffix' => '</div>',
	    );
	 
	    // Finally add the pager.
	    $build['pager'] = array(
	      '#type' => 'pager'
	    );

	    $build['#attached']['library'][] = 'food/form.review.manage';
	 
	    return $build;
	}


}
