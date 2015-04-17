<?php


class UserController extends \BaseController {

  /**
   * Display a listing of the resource.
   * GET /users
   *
   * @return Response
  */
  public function index()
  {
    //@TODO: set sensible limit here.
    $limit = Input::get('limit') ?: 20;
    $users = User::paginate($limit);
    return Response::json($users, 200);
  }


  /**
   * Store a newly created resource in storage.
   * POST /users
   *
   * @return Response
   */
  public function store()
  {
    $check = Input::only('email', 'mobile');
    $input = Input::all();

    // Does this user exist already?
    $user = User::where('email', '=', $check['email'])
                    ->orWhere('mobile', '=', $check['mobile'])
                    ->first();

    // If there is no user found, create a new one.
    if (!$user) {
      $user = new User;

      // This validation might not be needed, the only validation happening right now
      // is for unique email or phone numbers, and that should return a user
      // from the query above.
      if ($user->validate($input)) {
        $user->validate($input);
      } else {
        return Response::json($user->messages(), 401);
      }
    }
    // Update or create the user from all the input.
    try {
      //@TODO: is there a better way to get this to the mutator?
      if (Input::has('country')) {
        Session::flash('country', $input['country']);
      }
      foreach($input as $key => $value) {
        if ($key == 'interests'){
          $interests = array_map('trim', explode(',', $value));
          $user->push('interests', $interests, true);
        }
        elseif (!empty($value)) {
          $user->$key = $value;
        }
      }

      $user->save();

      $response = array(
        'created_at' => $user->created_at,
        '_id' => $user->_id
      );

      return Response::json($response, 201);
    }
    catch(\Exception $e) {
      return Response::json($e, 401);
    }
  }

  /**
   * Display the specified resource.
   *
   * @param $term - string
   *   term to search by (eg. mobile, drupal_id, id, email, etc)
   * @param $id - string
   *  the actual value to search for
   *
   * @return Response
   */
  public function show($term, $id)
  {
    $user = '';

    // Type cast id fields as ints.
    if (strpos($term,'_id') !== false && $term !== '_id') {
      $id = (int) $id;
    }

    // Find the user.
    $user = User::where($term, $id)->get();
    if(!$user->isEmpty()) {
      return Response::json($user, 200);
    }
    return Response::json('The resource does not exist', 404);

  }


  /**
   * Update the specified resource in storage.
   * PUT /users
   *
   * @return Response
   */
  public function update($id)
  {
    $input = Input::all();

    $user = User::where('_id', $id)->first();

    if($user instanceof User) {
      foreach($input as $key => $value) {
        if ($key == 'interests'){
          $interests = array_map('trim', explode(',', $value));
          $user->push('interests', $interests, true);
        }
        // Only update attribute if value is non-null.
        elseif(isset($key) && !is_null($value)) {
          $user->$key = $value;
        }
      }

      $user->save();

      $response = array('updated_at' => $user->updated_at);

      return Response::json($response, 202);
    }

    return Response::json("The resource does not exist", 404);
  }

  /**
   * Delete a user resource.
   * DELETE /users/:id
   *
   * @return Response
   */
  public function destroy($id)
  {
    $user = User::where('_id', $id)->first();

    if ($user instanceof User) {
      $user->delete();

      return Response::json("No Content", 204);
    }

    return Response::json("The resource does not exist", 404);
  }

  /**
   * Authenticate a registered user
   *
   * @return Response
   */
  public function login()
  {
    $input = Input::only('email', 'mobile', 'password');
    $user = new User;
    if($user->validate($input, true)) {
      if (Input::has('email')) {
        $user = User::where('email', '=', $input['email'])->first();
      }
      elseif (Input::has('mobile')) {
        $user = User::where('mobile', '=', $input['mobile'])->first();
      }
      if(!($user instanceof User)) {
        return Response::json("User is not registered.");
      }

      if(Hash::check($input['password'] , $user->password)) {
        $token = $user->login();
        $token->user = $user->toArray();

        $response = array(
          'email' => $user->email,
          'mobile' => $user->mobile,
          'created_at' => $user->created_at,
          'updated_at' => $user->updated_at,
          '_id' => $user->_id,
          'session_token' => $token->key
        );
        return Response::json($response, '200');
      }
      else {
        return Response::json("Incorrect password.", 412);
      }

    }
    else {
      return Response::json($user->messages(), 401);
    }

  }

  /**
   *  Logout a user: remove the specified active token from the database
   *  @param user User
   */
  public function logout()
  {
    if (!Request::header('Session')) {
      return Response::json('No token given.');
    }

    $input_token = Request::header('Session');
    $token = Token::where('key', '=', $input_token)->first();
    $user = Token::userFor($input_token);

    if (empty($token)) {
      return Response::json('No active session found.');
    }
    if ($token->user_id !== $user->_id) {
      Response::json('You do not own this token.');
    }
    if ($token->delete()){
      return Response::json('User logged out successfully.', 200);
    }
    else {
      return Response::json('User could not log out. Please try again.');
    }

  }

}