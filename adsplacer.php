<?php
/**
	Plugin Name: AdsPlace'r
	Plugin URI: https://wp-r.ru/plaginy/adsplacer-pro.html
	Description: Manage ad blocks in the articles separately for visitors to the PC and mobile OS
	Author: WP-R
	Version: 1.1.5
	Author URI: https://wp-r.ru/
	Text Domain: adsplacer
	Domain Path: /languages
	License: GPL
*/

defined('ABSPATH') or die('No script kiddies please!');

define('ADSPLACER_VERSION', '1.1.5');
define('ADSPLACER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ADSPLACER_PLUGIN_URL', plugin_dir_url(__FILE__));

class AdsPlacer {

	public $ads = array();
	public $device = false;

	public function __construct() {
		add_action('plugins_loaded', array($this, 'init_texdomain'));

		register_activation_hook(__FILE__, array($this, 'activation'));
		register_deactivation_hook(__FILE__, array($this, 'deactivation'));

		add_action('admin_enqueue_scripts',	array($this, 'admin_enqueue_scripts'));
		add_action('admin_menu', array($this, 'register_pages'));
		add_action('add_meta_boxes', array($this, 'add_metabox'));
		add_action('save_post_post', array($this, 'save_post'));

		$this->get_settings();

		add_filter('the_content', array($this, 'paste_ads_in_post'), 25);
		add_action('plugins_loaded', array($this, 'add_shortcodes'));
	}

	public function add_shortcodes() {
		$ads_shortcodes = array();
		for ($i=1; $i <= 5; $i++) {
			$ads_shortcodes['pc']['ads-pc-'.$i] = $this->ads['pc']['custom']['ads-'.$i];
			$ads_shortcodes['mobile']['ads-mob-'.$i] = $this->ads['mobile']['custom']['ads-'.$i];
		}

		global $shortcode_tags;
		global $sc_shortcodes_array;

		$device = $this->get_device();

		foreach ($ads_shortcodes['pc'] as $key => $value) {
			$sc_shortcodes_array[$key] = $device == 'pc' ? $value : '';
			$shortcode_tags[$key] = array($this, 'shortcode_replace');
		}

		foreach ($ads_shortcodes['mobile'] as $key => $value) {
			$sc_shortcodes_array[$key] = $device == 'mobile' ? $value : '';
			$shortcode_tags[$key] = array($this, 'shortcode_replace');
		}
	}

	public function shortcode_replace() {
		$args = func_get_args();
		global $sc_shortcodes_array;
		return stripslashes($sc_shortcodes_array[$args[2]]);
	}

	public function paste_ads_in_post($content) {
		if (is_single()) {
			global $post;
			$ads_exclude = get_post_meta($post->ID, 'adsplacer_ads', true);

			$show_all_ads = $show_before = $show_in = $show_after = true;
			if ($ads_exclude != false) {
				if ($ads_exclude['all'] == 'yes') {
					$show_all_ads = false;
				} else {
					$show_before = ($ads_exclude['before'] == 'yes') ? false : true;
					$show_in = ($ads_exclude['in'] == 'yes') ? false : true;
					$show_after = ($ads_exclude['after'] == 'yes') ? false : true;
				}
			}

			if ($show_all_ads !== false) {
				$device = $this->get_device();

				$pc_before = $this->ads[$device]['before'] != '' ? stripslashes($this->ads[$device]['before']) : '';
				$pc_in = $this->ads[$device]['in'] != '' ? stripslashes($this->ads[$device]['in']) : '';
				$pc_after = $this->ads[$device]['after'] != '' ? stripslashes($this->ads[$device]['after']) : '';

				if ($pc_in != '' && $show_in !== false) {
					$p_array = explode('</p>', $content);
					$p_count = count($p_array);

					$out_content = '';

					for ($i = 0; $i < $p_count; $i++) {
						if ($i == intval(($p_count / 2) - 1.5)) {
							$out_content .= $p_array[$i] . '</p><p>' . $pc_in . '</p>';
						} else {
							if ($i != 0) {
								$out_content .= '</p>';
							}
							$out_content .= $p_array[$i];
						}
					}

					$content = $out_content;
				}


				if ($pc_before != '' && $show_before !== false) {
					$content = '<p>' . $pc_before . '</p>' . $content;
				}

				if ($pc_after != '' && $show_after !== false) {
					$content = $content . '<p>' . $pc_after . '</p>';
				}
			}
		}

		return $content;
	}

	public function get_device() {
		if ($this->device === false) {
			$useragent = $_SERVER['HTTP_USER_AGENT'];
			if (preg_match('/android|mobile|blackberry|nokia|opera mini|cellphone|googlebot-mobile|iemobile|nintendo wii|nitro|playstation|proxinet|sonyericsson|sony|symbian|webos|windows ce|iphone|ipod|ipad|psp|vodafone|wap|xda|avantgo|xbox|kindle|maemo|htc/i', $useragent)) {
				$this->device = 'mobile';
				return 'mobile';
			} else {
				$this->device = 'pc';
				return 'pc';
			}
		} else {
			return $this->device;
		}
	}

	public function register_pages() {
		global $submenu;
		add_menu_page('AdsPlace\'r', 'AdsPlace\'r', 'manage_options', 'adsplacer', array($this, 'settings_html'), 'div');
		add_submenu_page('adsplacer', 'AdsPlace\'r — '.__('Settings', 'adsplacer'), __('Settings', 'adsplacer'), 'manage_options', 'adsplacer', array($this, 'settings_html'));
		add_submenu_page('adsplacer', 'AdsPlace\'r — '.__('Instruction', 'adsplacer'), __('Instruction', 'adsplacer'), 'manage_options', 'adsplacer_support', array($this, 'support_html'));
		if(get_locale() == 'ru_RU')
			$submenu['adsplacer'][] = array('AdsPlace\'r Pro<span class="update-plugins" aria-hidden="true" style="margin: -2px 0 -3px 2px;background-color: rgba(0, 0, 0, 0); width: 20px; height: 20px;"><svg width="20" height="20" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg" style="display: block;"><path fill="currentColor" d="M1728 647q0 22-26 48l-363 354 86 500q1 7 1 20 0 21-10.5 35.5t-30.5 14.5q-19 0-40-12l-449-236-449 236q-22 12-40 12-21 0-31.5-14.5t-10.5-35.5q0-6 2-20l86-500-364-354q-25-27-25-48 0-37 56-46l502-73 225-455q19-41 49-41t49 41l225 455 502 73q56 9 56 46z" style="fill: rgb(213, 78, 33);"></path></svg></span>', 'manage_options', 'https://wp-r.ru/plaginy/adsplacer-pro.html?utm_source=plugin&utm_medium=adsplacer&utm_campaign=menu');
	}

	public function admin_enqueue_scripts($page) {
		wp_enqueue_style('adsplacer_font_awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css', array(), false);
		wp_enqueue_style('adsplacer_backend_style', ADSPLACER_PLUGIN_URL.'assets/css/admin-style.css', array(), false);
		if ($page == 'toplevel_page_adsplacer' || $page == 'placer_page_adsplacer_support' || $page == 'post.php') {
			wp_enqueue_script('adsplacer_backend_js', ADSPLACER_PLUGIN_URL.'assets/js/jquery.adsplacer.js', array('jquery', 'jquery-ui-core', 'jquery-ui-tabs', 'jquery-effects-core', 'jquery-ui-widget'), false, true);
		}
	}

	public function add_metabox() {
		add_meta_box('adsplacer_exclude_ads_for_post', 'AdsPlace\'r', array($this, 'exclude_ads'), 'post', 'side');
	}

	public function exclude_ads() {
		global $post;

		$ads_exclude = array(
			'all' => array(
				'value' => 'no',
				'checked' => ''
			),
			'before' => array(
				'value' => 'no',
				'checked' => '',
				'disabled' => ''
			),
			'before' => array(
				'value' => 'no',
				'checked' => '',
				'disabled' => ''
			),
			'before' => array(
				'value' => 'no',
				'checked' => '',
				'disabled' => ''
			)
		);

		$ads = get_post_meta($post->ID, 'adsplacer_ads', true);
		$c = ' checked="checked"';
		$d = ' disabled';

		if ($ads != false) {
			foreach ($ads as $key => $value) {
				if ($key == 'all') {
					$ads_exclude['all']['value'] = $value;

					if ($value == 'yes') {
						$ads_exclude['all']['checked'] = $c;

						$ads_exclude['before']['disabled'] = $d;
						$ads_exclude['in']['disabled'] = $d;
						$ads_exclude['after']['disabled'] = $d;
					} else {
						$ads_exclude['all']['checked'] = '';

						$ads_exclude['before']['disabled'] = '';
						$ads_exclude['in']['disabled'] = '';
						$ads_exclude['after']['disabled'] = '';
					}
				} else {
					$ads_exclude[$key]['value'] = $value;

					if ($value == 'yes') {
						$ads_exclude[$key]['checked'] = $c;
					} else {
						$ads_exclude[$key]['checked'] = '';
					}
				}
			}
		}

		?>
		<p class="meta-options">
			<label for="adplacer_exclude_all" class="selectit">
				<input class="adsplacer_checkbox" name="adplacer_exclude_all" type="checkbox" value="yes" id="adplacer_exclude_all"<?php echo $ads_exclude['all']['checked']; ?>> <?php _e('Disable all ads in this article', 'adsplacer'); ?>
			</label><br/>
			<label for="adplacer_exclude_before" class="selectit">
				<input class="adsplacer_checkbox" name="adplacer_exclude_before" type="checkbox" value="yes" id="adplacer_exclude_before"<?php echo $ads_exclude['before']['checked'].$ads_exclude['before']['disabled']; ?>> <?php _e('Disable ads before the article', 'adsplacer'); ?>
			</label><br/>
			<label for="adplacer_exclude_in" class="selectit">
				<input class="adsplacer_checkbox" name="adplacer_exclude_in" type="checkbox" value="yes" id="adplacer_exclude_in"<?php echo $ads_exclude['in']['checked'].$ads_exclude['in']['disabled']; ?>> <?php _e('Disable ads in the middle of article', 'adsplacer'); ?>
			</label><br/>
			<label for="adplacer_exclude_after" class="selectit">
				<input class="adsplacer_checkbox" name="adplacer_exclude_after" type="checkbox" value="yes" id="adplacer_exclude_after"<?php echo $ads_exclude['after']['checked'].$ads_exclude['after']['disabled']; ?>> <?php _e('Disable ads after the article', 'adsplacer'); ?>
			</label>
		</p>
		<?php
	}

	public function save_post($post_id) {
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
		if (!current_user_can('edit_post', $post_id)) return;

		$ads_exclude = array(
			'all' => 'no',
			'before' => 'no',
			'in' => 'no',
			'after' => 'no'
		);

		foreach ($ads_exclude as $key => $exclude) {
			if (isset($_POST['adplacer_exclude_'.$key]) && $_POST['adplacer_exclude_'.$key] == 'yes') {
				$ads_exclude[$key] = 'yes';
			}
		}

		update_post_meta($post_id, 'adsplacer_ads', $ads_exclude);
	}

	public function settings_html() {
		$this->save_settings();
		$content = $this->get_content();
		$this->render_page($content);
	}

	public function get_settings() {
		$this->ads = get_option('adsplacer_ads');
	}

	public function render_page($settings) {
		$pc = $settings['pc'];
		$mobile = $settings['mobile'];

		?>
		<div class="wrap">

			<h2>AdsPlace'r <?php _e('Settings', 'adsplacer'); ?></h2>

            <?php if ( in_array( get_locale(), [ 'ru_RU', 'uk', 'kk', 'bel' ] ) ): ?>
                <div class="adsplacer-promo">
                    <div>
                        ⭐️ Получи больше возможностей в <a href="https://wp-r.ru/plaginy/adsplacer-pro.html?utm_source=plugin&utm_medium=adsplacer&utm_campaign=top" target="_blank" rel="noopener noreferrer">AdsPlace'r Pro</a>. Активируйте промокод сейчас и получите скидку 15%: <strong style="color: #f00;">adsplacer_pro</strong><br/>
                    </div>
                </div>
            <?php endif; ?>

			<form id="adsplacer_settings_form" method="POST" action="">
				<div id="adsplacer_settings_tabs">
					<ul>
						<li><a href="#tab-1"><?php _e('For PC', 'adsplacer'); ?></a></li>
						<li><a href="#tab-2"><?php _e('For Mobile', 'adsplacer'); ?></a></li>
					</ul>

					<div class="adsplacer-tabs" id="tab-1">
						<?php foreach ($pc['defined'] as $key => $value) { ?>
							<div class="tab-area">
								<h3><?=$value['title'];?></h3>
								<p><?=$value['description'];?></p>
								<?php
								$args = array(
									'wpautop' => 0,
									'media_buttons' => 1,
									'textarea_name' => $value['textarea_name'],
									'textarea_rows' => 10,
									'teeny'         => 1,
									'tinymce'       => 1,
									'quicktags'     => array(
										'id' => $key,
										'buttons' => 'strong,em,link,img,ul,ol,li'
									),
								);
								wp_editor($value['content'], $key, $args); ?>
							</div>
						<?php } ?>


						<div style="clear:both"></div>

                        <?php if ( in_array( get_locale(), [ 'ru_RU', 'uk', 'kk', 'bel' ] ) ): ?>
                            <div class="adsplacer-promo">
                                <div class="adsplacer-promo__item">
                                    <a href="https://wpshop.ru/?utm_source=plugin&utm_medium=adsplacer&utm_campaign=bottom" target="_blank" rel="noopener noreferrer">WPShop.ru</a>
                                    <div>премиум шаблоны и плагины</div>
                                </div>
                                <div class="adsplacer-promo__item">
                                    <a href="https://wpcourses.ru/?utm_source=plugin&utm_medium=adsplacer&utm_campaign=bottom" target="_blank" rel="noopener noreferrer">WPCourses.ru</a>
                                    <div>практические курсы WordPress</div>
                                </div>
                                <div class="adsplacer-promo__item">
                                    <a href="https://wpdetect.ru/?utm_source=plugin&utm_medium=adsplacer&utm_campaign=bottom" target="_blank" rel="noopener noreferrer">WPDetect.ru</a>
                                    <div>определитель шаблона и плагинов</div>
                                </div>
                                <div class="adsplacer-promo__item">
                                    <a href="https://wpaudit.ru/?utm_source=plugin&utm_medium=adsplacer&utm_campaign=bottom" target="_blank" rel="noopener noreferrer">WPAudit.ru</a>
                                    <div>бесплатный аудит сайта</div>
                                </div>
                            </div>
                        <?php endif; ?>

						<div style="clear:both"></div>


						<div class="custom-ads-tabs">
							<ul>
								<?php foreach ($pc['custom'] as $key => $value) { ?>
									<li><a href="#tab-pc-<?=$key;?>"><?php _e('Code ', 'adsplacer'); ?> <?=$key;?></a></li>
								<?php } ?>
							</ul>

							<?php foreach ($pc['custom'] as $key => $value) { ?>
								<div class="custom-ads-tab-area" id="tab-pc-<?=$key;?>">
									<h3><?=$value['title'];?></h3>
									<p><?=$value['description'];?></p>
									<?php
									$args = array(
										'wpautop' => 0,
										'media_buttons' => 1,
										'textarea_name' => 'pc[ads-' . $key . ']',
										'textarea_rows' => 10,
										'teeny'         => 1,
										'tinymce'       => 1,
										'quicktags'     => array(
											'id' => 'adsPc'.$key,
											'buttons' => 'strong,em,link,img,ul,ol,li'
										),
									);
									wp_editor($value['content'], 'adsPc'.$key, $args); ?>
								</div>
							<?php } ?>
						</div>
					</div>

					<div class="adsplacer-tabs" id="tab-2">
						<?php foreach ($mobile['defined'] as $key => $value) { ?>
							<div class="tab-area">
								<h3><?=$value['title'];?></h3>
								<p><?=$value['description'];?></p>
								<?php
								$args = array(
									'wpautop' => 0,
									'media_buttons' => 1,
									'textarea_name' => $value['textarea_name'],
									'textarea_rows' => 10,
									'teeny'         => 1,
									'tinymce'       => 1,
									'quicktags'     => array(
										'id' => $key,
										'buttons' => 'strong,em,link,img,ul,ol,li'
									),
								);
								wp_editor($value['content'], $key, $args); ?>
							</div>
						<?php } ?>

						<div style="clear:both"></div>

						<div class="custom-ads-tabs">
							<ul>
								<?php foreach ($mobile['custom'] as $key => $value) { ?>
									<li><a href="#tab-mob-<?=$key;?>"><?php _e('Code ', 'adsplacer');?> <?=$key;?></a></li>
								<?php } ?>
							</ul>

							<?php foreach ($mobile['custom'] as $key => $value) { ?>
								<div class="custom-ads-tab-area" id="tab-mob-<?=$key;?>">
									<h3><?=$value['title'];?></h3>
									<p><?=$value['description'];?></p>
									<?php
									$args = array(
										'wpautop' => 0,
										'media_buttons' => 1,
										'textarea_name' => 'mobile[ads-' . $key . ']',
										'textarea_rows' => 10,
										'teeny'         => 1,
										'tinymce'       => 1,
										'quicktags'     => array(
											'id' => 'adsMobile'.$key,
											'buttons' => 'strong,em,link,img,ul,ol,li'
										),
									);
									wp_editor($value['content'], 'adsMobile'.$key, $args); ?>
								</div>
							<?php } ?>
						</div>
					</div>
				</div>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save changes', 'adsplacer'); ?>"></p>
			</form>
		</div>
		<?php
	}

	public function get_content() {
		$this->get_settings();
		$ads = $this->ads;

		$settings = array(
			'pc' => array(
				'defined' => array(
					'beforeContent' => array(
						'title' => __('Before content', 'adsplacer'),
						'description' => __('Is shown before the main content of the article', 'adsplacer'),
						'textarea_name' => 'pc[before]',
						'content' => stripslashes($ads['pc']['before'])
					),
					'inContent' => array(
						'title' => __('In content', 'adsplacer'),
						'description' => __('Is shown exactly in the middle of the article', 'adsplacer'),
						'textarea_name' => 'pc[in]',
						'content' => stripslashes($ads['pc']['in'])
					),
					'afterContent' => array(
						'title' => __('After content', 'adsplacer'),
						'description' => __('Is shown directly after the content', 'adsplacer'),
						'textarea_name' => 'pc[after]',
						'content' => stripslashes($ads['pc']['after'])
					)
				),
				'custom' => array(
					'1' => array(
						'title' => __('Arbitrary advertising unit', 'adsplacer') . ' #1',
						'description' => __('Advertisement is shown in the arbitrary place of the site by means of a short-code', 'adsplacer') . ' <code class="adsplacer_code">[ads-pc-1]</code>',
						'content' => stripslashes($ads['pc']['custom']['ads-1'])
					),
					'2' => array(
						'title' => __('Arbitrary advertising unit', 'adsplacer') . ' #2',
						'description' => __('Advertisement is shown in the arbitrary place of the site by means of a short-code', 'adsplacer') . ' <code class="adsplacer_code">[ads-pc-2]</code>',
						'content' => stripslashes($ads['pc']['custom']['ads-2'])
					),
					'3' => array(
						'title' => __('Arbitrary advertising unit', 'adsplacer') . ' #3',
						'description' => __('Advertisement is shown in the arbitrary place of the site by means of a short-code', 'adsplacer') . ' <code class="adsplacer_code">[ads-pc-3]</code>',
						'content' => stripslashes($ads['pc']['custom']['ads-3'])
					),
					'4' => array(
						'title' => __('Arbitrary advertising unit', 'adsplacer') . ' #4',
						'description' => __('Advertisement is shown in the arbitrary place of the site by means of a short-code', 'adsplacer') . ' <code class="adsplacer_code">[ads-pc-4]</code>',
						'content' => stripslashes($ads['pc']['custom']['ads-4'])
					),
					'5' => array(
						'title' => __('Arbitrary advertising unit', 'adsplacer') . ' #5',
						'description' => __('Advertisement is shown in the arbitrary place of the site by means of a short-code', 'adsplacer') . ' <code class="adsplacer_code">[ads-pc-5]</code>',
						'content' => stripslashes($ads['pc']['custom']['ads-5'])
					),
				)
			),
			'mobile' => array(
				'defined' => array(
					'mobileBeforeContent' => array(
						'title' => __('Before content', 'adsplacer'),
						'description' => __('Is shown before the main content of the article', 'adsplacer'),
						'textarea_name' => 'mobile[before]',
						'content' => stripslashes($ads['mobile']['before'])
					),
					'mobileInContent' => array(
						'title' => __('In content', 'adsplacer'),
						'description' => __('Is shown exactly in the middle of the article', 'adsplacer'),
						'textarea_name' => 'mobile[in]',
						'content' => stripslashes($ads['mobile']['in'])
					),
					'mobileAfterContent' => array(
						'title' => __('After content', 'adsplacer'),
						'description' => __('Is shown directly after the content', 'adsplacer'),
						'textarea_name' => 'mobile[after]',
						'content' => stripslashes($ads['mobile']['after'])
					)
				),
				'custom' => array(
					'1' => array(
						'title' => __('Arbitrary advertising unit', 'adsplacer') . ' #1',
						'description' => __('Advertisement is shown in the arbitrary place of the site by means of a short-code', 'adsplacer') . ' <code class="adsplacer_code">[ads-mob-1]</code>',
						'content' => stripslashes($ads['mobile']['custom']['ads-1'])
					),
					'2' => array(
						'title' => __('Arbitrary advertising unit', 'adsplacer') . ' #2',
						'description' => __('Advertisement is shown in the arbitrary place of the site by means of a short-code', 'adsplacer') . ' <code class="adsplacer_code">[ads-mob-2]</code>',
						'content' => stripslashes($ads['mobile']['custom']['ads-2'])
					),
					'3' => array(
						'title' => __('Arbitrary advertising unit', 'adsplacer') . ' #3',
						'description' => __('Advertisement is shown in the arbitrary place of the site by means of a short-code', 'adsplacer') . ' <code class="adsplacer_code">[ads-mob-3]</code>',
						'content' => stripslashes($ads['mobile']['custom']['ads-3'])
					),
					'4' => array(
						'title' => __('Arbitrary advertising unit', 'adsplacer') . ' #4',
						'description' => __('Advertisement is shown in the arbitrary place of the site by means of a short-code', 'adsplacer') . ' <code class="adsplacer_code">[ads-mob-4]</code>',
						'content' => stripslashes($ads['mobile']['custom']['ads-4'])
					),
					'5' => array(
						'title' => __('Arbitrary advertising unit', 'adsplacer') . ' #5',
						'description' => __('Advertisement is shown in the arbitrary place of the site by means of a short-code', 'adsplacer') . ' <code class="adsplacer_code">[ads-mob-5]</code>',
						'content' => stripslashes($ads['mobile']['custom']['ads-5'])
					),
				)
			)
		);

		return $settings;
	}

	public function save_settings() {
		if (isset($_POST['pc']) && isset($_POST['mobile'])) {
			$pcBefore = trim($_POST['pc']['before']);
			$pcIn = trim($_POST['pc']['in']);
			$pcAfter = trim($_POST['pc']['after']);

			$pcCustom = array(
				'ads-1' => trim($_POST['pc']['ads-1']),
				'ads-2' => trim($_POST['pc']['ads-2']),
				'ads-3' => trim($_POST['pc']['ads-3']),
				'ads-4' => trim($_POST['pc']['ads-4']),
				'ads-5' => trim($_POST['pc']['ads-5'])
			);

			$mobileBefore = trim($_POST['mobile']['before']);
			$mobileIn = trim($_POST['mobile']['in']);
			$mobileAfter = trim($_POST['mobile']['after']);

			$mobileCustom = array(
				'ads-1' => trim($_POST['mobile']['ads-1']),
				'ads-2' => trim($_POST['mobile']['ads-2']),
				'ads-3' => trim($_POST['mobile']['ads-3']),
				'ads-4' => trim($_POST['mobile']['ads-4']),
				'ads-5' => trim($_POST['mobile']['ads-5'])
			);

			$insert = array(
				'pc' => array(
					'before' => $pcBefore,
					'in' => $pcIn,
					'after' => $pcAfter,
					'custom' => $pcCustom
				),
				'mobile' => array(
					'before' => $mobileBefore,
					'in' => $mobileIn,
					'after' => $mobileAfter,
					'custom' => $mobileCustom
				)
			);

			update_option('adsplacer_ads', $insert, true);
		}

		return true;
	}

	public function support_html() {
		?>
		<div class="wrap instruction">
			<h2><?php _e('Instruction', 'adsplacer'); ?></h2>

			<?php if (get_locale() == 'ru_RU') { ?>
				<p>Если не любите читать, посмотрите видео.
				<p><iframe width="560" height="315" src="https://www.youtube.com/embed/W56OM4lkq5E?rel=0" frameborder="0" allowfullscreen></iframe>
				<p>А вот <a href="http://mojwp.ru/adsplacer-cache.html" target="_blank">инструкция как настроить свой плагин кеширования</a>, чтобы он корректно работал для пользователей с разных платформ (PC и мобильных).
				<p>При помощи AdsPlace’r вы сможете показывать разные блоки рекламы посетителям своего сайта, которые зашли с PC и которые зашли с мобильного гаджета (смартфон или планшет). 
				<p>Плагин AdsPlace'r автоматически вставляет ваши рекламные блоки:
					<ul>
						<li>Перед контентом.</li>
						<li>Ровно по центру статьи (высчитывает количество абзацев и определяет центр).</li>
						<li>После контента.</li>
						<li>В любом произвольном месте сайта при помощи шорткода (до 5 штук разных шорткодов).</li>
					</ul>
				<p>На странице настроек есть 2 основные вкладки – Для PC и Для Мобильных ОС.
				<p>Каждая вкладка имеет одинаковый набор блоков для вставки кода. При этом есть панель форматирования текста в этих блоках, если вы захотите там разместить произвольный текст.
				<p>Блоки подписаны и позволяют выбрать место, где отображать рекламу.
				<p>Ниже есть возможность использовать шорткоды для вставки рекламных блоков в произвольное место статьи. Вы можете сделать сразу 5 шорткодов для PC и 5 для Мобильных ОС.
				<div class="autor_plugin">
					<h3>Разработчики</h3>
                    <div>
                        <img src="http://1.gravatar.com/avatar/75ca97921a2a840bdc60a9b66c363d9b?s=50&d=mm&r=g" alt="" />
                        <a href="https://wpshop.ru/?utm_source=plugin&utm_medium=adsplacer&utm_campaign=developer" target="_blank">WPShop.ru</a><br/>Разработка<br/>PHP & WP Developer
                    </div>
					<div>
						<img src="https://www.gravatar.com/avatar/5480336fef49a6c9a0c15beea7771941?d=mm&s=50&r=G" alt="" />
                        <a href="http://mojwp.ru/" target="_blank">Виталик mojWP</a><br/>Автор<br/><a href="https://wp-r.ru/?utm_source=plugin&utm_medium=adsplacer&utm_campaign=developer" target="_blank">Магазин WordPress</a>
					</div>
				</div>
			<?php } else { ?>
			
			
				<div class="autor_plugin">
					<p>By means of the AdsPlace’r you will be able to show different units of advertisement to visitors of your site, who use PC or mobile gadget (smartphone or tablet).
					<p>The AdsPlace’r plug-in inserts your advertising units automatically:
						<ul>
							<li>Before the content</li>
							<li>Exactly in the middle of the article (the number of paragraphs is calculated and the center is defined)</li>
							<li>After the content</li>
							<li>In any arbitrary place of the site by means of a short-code (up to 5 different short-codes)</li>
						</ul>
					<p>On the setting’s page there are two main tabs – for PC and for the mobile OS.
					<p>Each tab has an identical set of the units for the code insertion. If you want to place the arbitrary text there is also the text formatting toolbar in these units.
					<p>Units are signed and allow selecting the place where to display the advertisement.
					<p>Below there is an opportunity to use the sort-codes for to insert the advertising units in arbitrary place of the article. You can create directly 5 short-codes for PC and 5 for mobile OS.
					<h3>Development</h3>
					<div>
						<img src="https://www.gravatar.com/avatar/5480336fef49a6c9a0c15beea7771941?d=mm&s=50&r=G" alt="Vitalik" /><a href="http://mojwp.ru" target="_blank">Vitalik mojWP</a><br/>Author<br/>SEO, HTML/CSS
					</div>
				</div>
			<?php } ?>
		</div>
		<?php
	}

	public function init_texdomain() {
		load_plugin_textdomain('adsplacer', false, dirname(plugin_basename(__FILE__)).'/languages/' );
	}

	public function activation() {

	}

	public function deactivation() {

	}

}

new AdsPlacer();

?>