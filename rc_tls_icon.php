<?php

class rc_tls_icon extends rcube_plugin
{	
	private $message_headers_done = false;
	private $icon_img = '';

	function init()
	{
		$this->add_hook('message_headers_output', array($this, 'message_headers'));
		$this->add_hook('storage_init', array($this, 'storage_init'));
		
		$this->include_stylesheet('rc_tls_icon.css');

		$this->add_texts('localization/');
	}
	
	public function storage_init($p)
	{
		$p['fetch_headers'] = trim(($p['fetch_headers'] ?? '') . ' ' . strtoupper('Received'));
		return $p;
	}
	
	public function message_headers($p)
	{
		if ($this->message_headers_done === false) {
			$this->message_headers_done = true;

			$icondir = "plugins/" . $this->ID . "/icons/";

			$Received_Header = $p['headers']->others['received'];
			if (is_array($Received_Header)) {
				$Received = $Received_Header[0];
			} else {
				$Received = $Received_Header;
			}
			
			if ($Received == null) {
				// There was no Received Header. Possibly an outbound mail. Do nothing.
				return $p;
			}

            $rcmail = rcmail::get_instance();
            $tls_icon_headers = $rcmail->config->get('tls_icon_headers', array());
            if (!is_array($tls_icon_headers) || count($tls_icon_headers) == 0) {
                return $p;
            }

            $found = false;
            foreach (array_values($tls_icon_headers) as $m_config) {
                $pre_needle  = $m_config['pre'];
                $pattern     = $m_config['pattern'];
                $post_needle = $m_config['post'];

                if (preg_match_all("/$pattern/im", $Received, $items, PREG_PATTERN_ORDER)) {
                    if (isset($items[0][0])) {
                        $data = $items[0][0];

                        $pos = strpos($data, $pre_needle);
                        $data = substr_replace($data, "", $pos, strlen($pre_needle));

                        $pos = strrpos($data, $post_needle);
                        $data = substr_replace($data, "", $pos, strlen($post_needle));

                        $this->icon_img .= '<img class="lock_icon" src="' . $icondir . 'lock.svg" title="'. htmlentities($data) .'" />';
                        $found = true;
                    }
                } else if (preg_match_all('/\[127.0.0.1\]|\[::1\]/im', $Received, $items, PREG_PATTERN_ORDER)) {
                    $this->icon_img .= '<img class="lock_icon" src="' . $icondir . 'blue_lock.svg" title="' . $this->gettext('internal') . '" />';
                    $found = true;
                }

                if ($found) {
                    break;
                }
            }

            if (!$found) {
				$this->icon_img .= '<img class="lock_icon" src="' . $icondir . 'unlock.svg" title="' . $this->gettext('unencrypted') . '" />';
            }
		}

		if (isset($p['output']['subject'])) {
			$p['output']['subject']['value'] = htmlentities($p['output']['subject']['value']) . $this->icon_img;
			$p['output']['subject']['html']  = 1;
		}

		return $p;
	}
}
