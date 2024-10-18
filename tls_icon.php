<?php

class tls_icon extends rcube_plugin
{
	private $message_headers_done = false;
	private $icon_img = '';

	function init()
	{
		$this->load_config();
		$this->add_hook('message_headers_output', array($this, 'message_headers'));
		$this->add_hook('storage_init', array($this, 'storage_init'));

		$this->include_stylesheet('tls_icon.css');

		$this->add_texts('localization/');
	}

	public function storage_init($p)
	{
		$p['fetch_headers'] = trim(($p['fetch_headers'] ?? '') . ' ' . strtoupper('received'));
		return $p;
	}

	public function message_headers($p)
	{
		if ($this->message_headers_done === false) {
			$this->message_headers_done = true;

			$rcmail = rcmail::get_instance();
			$tls_icon_headers = $rcmail->config->get('tls_icon', array());
			if (!is_array($tls_icon_headers) || count($tls_icon_headers) == 0) {
				return $p;
			}

			$icondir = "plugins/" . $this->ID . "/icons/";

			$received_Headers = $p['headers']->others['received'];

			// Search Header which machtes the header_pattern
			$received = "";
			foreach ($received_Headers as $header) {
				if (preg_match('/' . $tls_icon_headers['header_pattern'] . '/i', $header)) {
					$received = $header;
					break;
				}
			}

			if ($received == null || $received == "") {
				// There was no received Header. Possibly an outbound mail. Do nothing.
				return $p;
			}

			$found = false;
			// Search for TLS in received Header
			if (preg_match('/' . $tls_icon_headers['check_pattern'] . '/i', $received)) {

				// Search for tooltip in received Header
				$tooltip = $this->gettext('encrypted');
				if (preg_match('/' . $tls_icon_headers['tooltip_pattern'] . '/i', $received, $matches)) {
					for($i = 1; $i < count($matches); $i++) {
						if ($matches[$i] != "") {
							$tooltip .= ": ". $matches[$i];
							break;
						}
					}
				}
				$this->icon_img .= '<img class="lock_icon" src="' . $icondir . 'lock_green.png" title="' . htmlentities($tooltip) . '" />';
				$found = true;
			} else if (preg_match('/' . $tls_icon_headers['local_pattern'] . '/i', $received)) {
				$this->icon_img .= '<img class="lock_icon" src="' . $icondir . 'lock_blue.png" title="' . $this->gettext('internal') . '" />';
				$found = true;
			}

			if (!$found) {
				$this->icon_img .= '<img class="lock_icon" src="' . $icondir . 'unlock.png" title="' . $this->gettext('unencrypted') . '" />';
			}
		}

		if (isset($p['output']['subject'])) {
			$p['output']['subject']['value'] = htmlentities($p['output']['subject']['value']) . $this->icon_img;
			$p['output']['subject']['html']  = 1;
		}

		return $p;
	}
}
