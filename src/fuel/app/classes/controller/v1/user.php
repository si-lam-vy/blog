<?php
use Model\V1\User;
use Fuel\Core\Controller_Rest;
use Auth\Auth;

/**
 * The User Controller.
 *
 * A user controller have function for user use the blog system
 * 
 *
 * @package  app
 * @extends  Controller_Rest for API 
 */
class Controller_V1_User extends Controller_Rest
{
	//return json format
	protected $format='json';
	/**
	 * The basic welcome message
	 *
	 * @access  public
	 * @return  Response
	 */
	public function action_index()
	{
		return $this->response(array('meta'=>array('message'=>'Welcome to blog system'),
		));
	}
	
	
	/**
	 * A function post_index to create user
	 * method post
	 * @link http://localhost/v1/public/user/
	 * @access  public
	 * @return  Response
	 */
	public function post_index()
	{
		//create user data from input post
		//clean data
		$filters = array('strip_tags', 'htmlentities', '\\cleaners\\soap::clean');
		//username use to login the system
		$data['username']=Security::clean(Input::post('username'),$filters);
		//email use to login the system
		$data['email']=Input::post('email');
		//password use to login, will be encrypted
		$data['password']=Security::clean(Input::post('password'),$filters);
		
		//information of user
		$data['lastname']=Input::post('lastname');
		$data['firstname']=Input::post('firstname');
		
		//validation data user
		$result=User::validate_user();
		//check result
		if ($result!=1)//have error message
		{
			//response the error code and message;
			$code='1001';
			return $this->response(array('meta'=>array(
				'code'=>$code,
				'description'=>'Input validation failed',
				'message'=>$result
			),'data'=>null));
		}else{
			//return 1 for valid data
			
			//try catch to check and insert user into db
			try{
				//check username exist or not return true if exist, else return false
				$status=User::check_user_exist($data['username']);	
				//return $this->response(array($status));
								
				if($status)
				{
					
					//return error code and message
					$code='2001';
					return $this->response(array('meta'=>array(
							'code'=>$code,
							'description'=>'Username exist in database',
							'message'=>'This username is already in used'),
							'data'=>null)
					);
				}else{
									
					//insert db
					//set date time for create account and modified by current date time
					$time=time();
					$data['created_at']=$time;
					$data['modified_at']=$time;				
					//hash password before insert into db
					//hash password by auth package, password become :12ac1f48d9649....**
					$data['password']=Auth::hash_password($data['password']);
					$rs=User::create_user($data);
					
					if($rs){
					//login and create token for new user,use password before encrypted
					$user=User::create_token($data['username'], Security::clean(Input::post('password'),$filters));
					//add remember me cookie for check logged in
					Auth::remember_me();
					//response code 200 for success
					$code='200';
					
					return $this->response(array('meta'=>array(
						'code'=>$code,
						 'message'=>'Account created success',
					),
					'data'=>$user,
					));
					}else{
						$code='9005';
						return $this->response(array('meta'=>array(
								'code'=>$code,
								'description'=>'can\'t insert into database',
								'message'=>$status,
						'data'=>null)));
					}
				}
				
				
			}catch(Exception $ex)
			{
				return $ex->getMessage();
			}
			
		}

		
	}

	/**
	 * The 404 action for the application.
	 *
	 * @access  public
	 * @return  Response
	 */
	public function action_404()
	{
		return Response::forge(Presenter::forge('welcome/404'), 404);
	}
	
	
	 
}
