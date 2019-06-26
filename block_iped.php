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

    function content_is_trusted() {
        global $SCRIPT;

        if (!$context = context::instance_by_id($this->instance->parentcontextid, IGNORE_MISSING)) {
            return false;
        }
        //find out if this block is on the profile page
        if ($context->contextlevel == CONTEXT_USER) {
            if ($SCRIPT === '/my/index.php') {
                // this is exception - page is completely private, nobody else may see content there
                // that is why we allow JS here
                return true;
            } else {
                // no JS on public personal pages, it would be a big security issue
                return false;
            }
        }

        return true;
    }

    /**
     * The block should only be dockable when the title of the block is not empty
     * and when parent allows docking.
     *
     * @return bool
     */
    public function instance_can_be_docked() {
        return (!empty($this->config->title) && parent::instance_can_be_docked());
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
            'user_token' => $user_token->token,
            'user_id' => $user_token->iped_user_id,
            'results' => 999999,
            'external_lms' => 1,
            'inprogress' => 1,
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

      $region = $this->instance->region;
      $data = "<style>
                .iped-content {
                  overflow-y: scroll;
                  overflow-x:hidden;
                  max-height:500px;
                }
                .iped-content ul {
                  list-style: none;
                  margin: 0;
                  padding: 0;
                }
                .iped-content ul li {
                  clear: both;
                }

                .iped-content ul li hr {
                  margin: 10px 0 !important;
                }

                .iped-course {
                }

                .iped-course-image {
                  float: left;
                  margin-right: 15px;
                  width: 5%;
                }
                  .iped-course-image img {
                    width: 32px;
                    height: 32px;
                    border-radius: 50%;
                  }

                  .iped-course-title {
                    float: left;
                    width: 70%;
                  }";

                  if( $region != 'content') {

                    $data .= ".iped-course-title {
                      float: left;
                      width: 100%;
                    }";

                  }

          $data .= ".iped-progress {
                    display: flex;
                    height: 1rem;
                    overflow: hidden;
                    font-size: .703125rem;
                    background-color: #e9ecef;
                  }

                  .iped-progress .iped-progressbar {
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    color: #fff;
                    text-align: center;
                    background-color: #f0ad4e;
                    -webkit-transition: width .6s ease;
                    -o-transition: width .6s ease;
                    transition: width .6s ease;
                  }

                  .iped-button {
                    margin-top: 10px;
                    float: right;
                  }

                  .iped-button a {
                    display: inline-block;
                    *display: inline;
                    *zoom: 1;
                    padding: 4px 10px;
                    margin-bottom: 0;
                    line-height: 15px;
                    text-align: center;
                    vertical-align: middle;
                    cursor: pointer;
                    color: #333;
                    text-shadow: 0 1px 1px rgba(255,255,255,0.75);
                    background-color: #f5f5f5;
                    background-image: -moz-linear-gradient(top, #fff, #e6e6e6);
                    background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#fff), to(#e6e6e6));
                    background-image: -webkit-linear-gradient(top, #fff, #e6e6e6);
                    background-image: -o-linear-gradient(top, #fff, #e6e6e6);
                    background-image: linear-gradient(to bottom, #fff, #e6e6e6);
                    background-repeat: repeat-x;
                    filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffffffff', endColorstr='#ffe6e6e6', GradientType=0);
                    border-color: #e6e6e6 #e6e6e6 #bfbfbf;
                    border-color: rgba(0,0,0,0.1) rgba(0,0,0,0.1) rgba(0,0,0,0.25);
                    *background-color: #e6e6e6;
                    filter: progid:DXImageTransform.Microsoft.gradient(enabled = false);
                    border: 1px solid #ccc;
                    *border: 0;
                    border-bottom-color: #b3b3b3;
                    -webkit-border-radius: 4px;
                    -moz-border-radius: 4px;
                    border-radius: 4px;
                    *margin-left: .3em;
                    -webkit-box-shadow: inset 0 1px 0 rgba(255,255,255,.2), 0 1px 2px rgba(0,0,0,.05);
                    -moz-box-shadow: inset 0 1px 0 rgba(255,255,255,.2), 0 1px 2px rgba(0,0,0,.05);
                    box-shadow: inset 0 1px 0 rgba(255,255,255,.2), 0 1px 2px rgba(0,0,0,.05);

                    text-transform: uppercase;
                    font-size: 9px;
                    color: #fff;
                    text-shadow: 0 -1px 0 rgba(0,0,0,0.25);
                    background-color: #49afcd;
                    background-image: -moz-linear-gradient(top, #5bc0de, #2f96b4);
                    background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#5bc0de), to(#2f96b4));
                    background-image: -webkit-linear-gradient(top, #5bc0de, #2f96b4);
                    background-image: -o-linear-gradient(top, #5bc0de, #2f96b4);
                    background-image: linear-gradient(to bottom, #5bc0de, #2f96b4);
                    background-repeat: repeat-x;
                    filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff5bc0de', endColorstr='#ff2f96b4', GradientType=0);
                    border-color: #2f96b4 #2f96b4 #1f6377;
                    border-color: rgba(0,0,0,0.1) rgba(0,0,0,0.1) rgba(0,0,0,0.25);
                    *background-color: #2f96b4;
                    filter: progid:DXImageTransform.Microsoft.gradient(enabled = false);
                  }
                  </style>";

        $data .= '<div class="iped-content"><ul>';

        foreach ( $courses as $course ) {
          $course_progress = 0;

          if ( ! empty( $course->course_user ) ) {
            $course_progress = $course->course_user->user_course_completed;
          }

          $data .= '<li>
                    <div class="iped-course">';

          // Se está no centro mostra a imagem
          if( $region == 'content' ) {
            $data .= '<div class="iped-course-image">
                          <a href="https://www.iped.com.br/"><img src="'.$course->course_image.'"></a>
                        </div>';
          }
          $data .= '<div class="iped-course-title">
                      <a href="'.$course->course_iframe_url.'" target="_blank"><strong>'.$course->course_title.'</strong></a>';

          // O curso iniciou?
          if ( $course_progress != 0 ) {
            $data .= '<div class="iped-progress"><div class="iped-progressbar" role="progressbar" style="width: '.$course_progress.'%" aria-valuenow="'.$course_progress.'" aria-valuemin="0" aria-valuemax="100">'.$course_progress.'%</div></div>';
          }

          $data .= '</div><!-- iped course title -->';

          // Está na centro?
          if( $region == 'content') {
            $data .= '<div class="iped-button">
                              <a href="'.$course->course_iframe_url.'" target="_blank"><strong>Ir para o Curso</strong></a>
                            </div>';
          }

          $data .= '</div>';
          $data .= '<div class="clearfix"></div>';
          $data .= '<hr /></li>';
        }
        $data .=  '</ul></div>';

        return $data;
    }

    private function get_user_iped_token($user, $token)
    {

        global $DB;

        $iped_token = $DB->get_record_sql('SELECT token, iped_user_id FROM {iped} WHERE user_id = ? ', [$user->id]);

        if ($iped_token !== false) {

            //return $iped_token->token;
            return $iped_token;
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
        $data->iped_user_id = $iped_token->USER_ID;
        $data->token = $iped_token->USER_TOKEN;
        $DB->insert_record('iped', $data);

        //return $iped_token->USER_TOKEN;
        return $iped_token;
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
