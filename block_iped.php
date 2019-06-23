<?php

class block_iped extends block_base
{
    public function init()
    {
        $this->title = 'Cursos'; //get_string('pluginname', 'block_iped');
    }

    public function get_content()
    {
        global $USER, $DB;

        if ($this->content !== null) {
            return $this->content;
        }

        $iped_courses = $this->get_iped_courses( $USER );

        $this->content = new stdClass();
        $this->content->text = $iped_courses;
        //$this->content->footer = 'block footer';

        return $this->content;
    }

    public function instance_allow_multiple()
    {
        return true;
    }

    public function has_config()
    {
        return true;
    }


    private function get_iped_courses($user)
    {

        // make a connection to list

        $token = get_config('block_iped', 'ipedtoken');

        if (empty($token) || $token === false ) {
            return '';
        }

        $user_token = $this->get_user_iped_token($user, $token);

        if (empty($user_token)) {
            return '';
        }

        $url = 'http://www.iped.com.br/api/course/get-courses';
        $args = [
            'token' => $token,
            'user_token' => $user_token,
            'results' => 999999,
        ];

        $get_courses = $this->iped_call($url, $args);

        if ( empty( $get_courses ) ) {
            return '';
        }

        $course = $this->beautifier_course( $get_courses->COURSES );
        return $course;
    }

    private function beautifier_course( $courses )
    {

        $data = '<div class="card-text mt-3 pr-3" style="overflow-y: scroll; overflow-x:hidden; height:500px;"> <ul class="pl-0 list-group list-group-flush">';

        foreach ( $courses as $course ) {

            $course_progress = 0;

            if ( ! empty( $course->course_user ) ) {

                $course_progress = $course->course_user->user_course_completed;
            }

            $data .= '<li class="list-group-item pl-0 pr-0">
                        <div class="row">
                            <div class="col-8 pr-0">
                                <div class="d-flex flex-row align-items-center" style="height: 32px">';

            $data .= "<img src='{$course->course_image}' class='bg-pulse-grey rounded-circle'  style='height: 32px; width: 32px;'>";
            $data .= "<div style='flex: 1' class='pl-2'>
                        <a href='https://www.iped.com.br/{$course->course_slug}' target='_blank'><strong>{$course->course_title}</strong></a>";

            if ( $course_progress != 0 ) {
                $data .= "<div class='progress'>
                            <div class='progress-bar bg-warning' role='progressbar' style='width: {$course_progress}%' aria-valuenow='{$course_progress}' aria-valuemin='0' aria-valuemax='100'>{$course_progress}%</div>
                        </div>";
            }

            $data .= '</div>';

            $data .= "</div>
                        </div>
                        <div class='col-4 pr-3'>
                            <div class='d-flex flex-row justify-content-end' style='height: 32px; padding-top: 2px'>
                                <a href='https://www.iped.com.br/{$course->course_slug}' target='_blank' class='btn btn-sm btn-info text-uppercase'><strong>Ir para o Curso</strong></a>
                            </div>
                        </div>
                    </div>
                </li>";
        }

        $data .= '</ul> </div>';

        return $data;
    }

    private function get_user_iped_token($user, $token)
    {

        global $DB;

        $iped_token = $DB->get_record_sql('SELECT token FROM {iped} WHERE user_id = ? ', [$user->id]);

        if ($iped_token !== false) {

            return $iped_token->token;
        }

        $url = 'https://www.iped.com.br/api/user/login-auth';
        $args = [
            'token'      => $token,
            'user_name'  => $user->username,
            'user_email' => $user->email,
        ];

        $iped_token = $this->iped_call($url, $args);

        if(empty($iped_token->USER_TOKEN)) {
            return '';
        }

        $data = new stdClass();
        $data->user_id = $user->id;
        $data->token = $iped_token->USER_TOKEN;
        $DB->insert_record('iped', $data);

        return $iped_token->USER_TOKEN;
    }

    private function iped_call($url, $args)
    {

        // Get cURL resource
        $curl = curl_init();
        // Set some options - we are passing in a useragent too here
        curl_setopt_array( $curl, [
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_URL => $url,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $args,
        ] );

        // Send the request & save response to $resp
        $resp = curl_exec($curl);
        // Close request to clear up some resources
        curl_close($curl);

        return json_decode( $resp );
    }
}
