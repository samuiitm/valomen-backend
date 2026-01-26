<?php

/* ===== Index ===== */
Router::get('',  'HomeController@index');
Router::get('/', 'HomeController@index');

/* ===== Login/Register ===== */
Router::get('login',    'AuthController@showLoginForm');
Router::post('login',   'AuthController@processLogin');
Router::get('logout',   'AuthController@logout');
Router::get('register', 'AuthController@showRegisterForm');
Router::post('register','AuthController@processRegister');

/* ===== OAuth ===== */
Router::get('auth/google',          'OAuthController@googleRedirect');
Router::get('auth/google/callback', 'OAuthController@googleCallback');

Router::get('auth/github',          'OAuthController@githubRedirect');
Router::get('auth/github/callback', 'OAuthController@githubCallback');

/* ===== Password Reset ===== */
Router::get('forgot_password', 'PasswordResetController@showForgotPasswordForm');
Router::post('forgot_password', 'PasswordResetController@processForgotPasswordForm');

Router::get('reset_password', 'PasswordResetController@showResetPasswordForm');
Router::post('reset_password', 'PasswordResetController@processResetPasswordForm');

/* ===== Profile ===== */
Router::get('profile',  'UserProfileController@showProfile');

Router::get('profile/username',  'UserProfileController@showChangeUsernameForm');
Router::post('profile/username', 'UserProfileController@changeUsernameAction');

Router::get('profile/password',  'UserProfileController@showChangePasswordForm');
Router::post('profile/password', 'UserProfileController@changePasswordAction');

Router::post('profile/avatar',          'UserProfileController@uploadAvatarAction');
Router::post('profile/avatar/confirm',  'UserProfileController@confirmAvatarAction');

/* ===== Matches ===== */
Router::get('matches',  'MatchesController@index');

Router::get('match_create', 'MatchAdminController@createFormAction');
Router::post('match_create','MatchAdminController@createPostAction');

Router::get('match_edit',   'MatchAdminController@editFormAction');
Router::post('match_edit',  'MatchAdminController@editPostAction');

Router::get('match_delete', 'MatchAdminController@deleteAction');

/* ===== Predictions ===== */
Router::get('predict',            'PredictionController@showPredictFormAction');
Router::post('predict',           'PredictionController@savePredictAction');
Router::get('my_predictions',     'PredictionController@myPredictionsAction');
Router::get('prediction_delete',  'PredictionController@deletePredictionAction');

/* ===== Admin Panel ===== */
Router::get('admin', 'AdminPageController@index');

Router::get('admin/user_delete', 'AdminPanelController@deleteUserAction');
Router::get('admin/team_delete', 'AdminPanelController@deleteTeamAction');

Router::get('admin/user_edit',   'AdminPanelController@editUserFormAction');
Router::post('admin/user_edit',  'AdminPanelController@editUserPostAction');

Router::get('admin/team_edit',   'AdminPanelController@editTeamFormAction');
Router::post('admin/team_edit',  'AdminPanelController@editTeamPostAction');

Router::get('admin/team_create', 'AdminPanelController@createTeamFormAction');
Router::post('admin/team_create','AdminPanelController@createTeamPostAction');

/* ===== Events ===== */
Router::get('events', 'EventsController@index');

Router::get('event_create', 'EventAdminController@createFormAction');
Router::post('event_create','EventAdminController@createPostAction');

Router::get('event_edit',   'EventAdminController@editFormAction');
Router::post('event_edit',  'EventAdminController@editPostAction');

Router::get('event_delete', 'EventAdminController@deleteAction');