<?php

Router::get('/', 'HomeController@index');

Router::get('/login',    'AuthController@showLoginForm');
Router::post('/login',   'AuthController@processLogin');
Router::get('/logout',   'AuthController@logout');
Router::get('/register', 'AuthController@showRegisterForm');
Router::post('/register','AuthController@processRegister');

Router::get('/profile',  'UserProfileController@showProfile');
Router::post('/profile', 'UserProfileController@updateProfileAction');

Router::get('/matches',  'MatchesController@index');

Router::get('/match_create', 'MatchAdminController@createFormAction');
Router::post('/match_create','MatchAdminController@createPostAction');

Router::get('/match_edit',   'MatchAdminController@editFormAction');
Router::post('/match_edit',  'MatchAdminController@editPostAction');

Router::get('/match_delete', 'MatchAdminController@deleteAction');

Router::get('/predict',            'PredictionController@showPredictFormAction');
Router::post('/predict',           'PredictionController@savePredictAction');
Router::get('/my_predictions',     'PredictionController@myPredictionsAction');
Router::get('/prediction_delete',  'PredictionController@deletePredictionAction');

Router::get('/admin', 'AdminPageController@index');

Router::get('/user_delete', 'AdminPanelController@deleteUserAction');
Router::get('/team_delete', 'AdminPanelController@deleteTeamAction');

Router::get('/user_edit',   'AdminPanelController@editUserFormAction');
Router::post('/user_edit',  'AdminPanelController@editUserPostAction');

Router::get('/team_edit',   'AdminPanelController@editTeamFormAction');
Router::post('/team_edit',  'AdminPanelController@editTeamPostAction');

Router::get('/team_create', 'AdminPanelController@createTeamFormAction');
Router::post('/team_create','AdminPanelController@createTeamPostAction');

Router::get('/events', 'EventsController@index');

Router::get('/event_create', 'EventAdminController@createFormAction');
Router::post('/event_create','EventAdminController@createPostAction');

Router::get('/event_edit',   'EventAdminController@editFormAction');
Router::post('/event_edit',  'EventAdminController@editPostAction');

Router::get('/event_delete', 'EventAdminController@deleteAction');