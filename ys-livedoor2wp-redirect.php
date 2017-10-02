<?php
/**
 * Plugin Name:     YS livedoor2wp redirect
 * Description:     ライブドアブログ時代のURLを上手いことWordPress時代のURLにリダイレクトするプラグイン（暫定的に）
 * Author:          yosiakatsuki
 * Author URI:      https://yosiakatsuki.net/
 * Version:         1.0.0
 *
 * @package         ys-livedoor2wp-redirect
 */

/*
	Copyright (c) 2016 Yoshiaki Ogata (https://yosiakatsuki.net/)
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
function l2wpr_redirect() {
	// 404の時にライブドアブログの時のURLか判断してリダイレクトかける
  if( is_404() ){

		$request_url = $_SERVER["REQUEST_URI"];
		$url = home_url();

		// パーマリンク設定に.htmlを入れるとライブドアブログ時代以外でも処理してしまう
		if ( strpos( $request_url, '.html' ) !== FALSE){

			$temp = substr( $request_url, strrpos($request_url, '/') + 1);
			$temp = str_replace( '.html', '', $temp );
			$post = get_page_by_path( $temp, OBJECT, 'post' );
			if( $post ){
				$post_id = $post->ID;
				$redirect_url = get_permalink( $post_id );

				$key_hit = 'l2wpr_redirect_cnt';
				$val_hit = get_post_meta( $post_id, $key_hit, true );
				if( ! $val_hit || ! is_numeric( $val_hit ) ){
					$val_hit = 0;
				} else {
					$val_hit = (int)$val_hit;
				}
				$val_hit ++;

				update_post_meta( $post_id, $key_hit, $val_hit );
				update_post_meta( $post_id, 'l2wpr_redirect_last_time', date_i18n( 'Y/m/d H:i:s' ) );

				// リダイレクト
				wp_safe_redirect( $redirect_url, 301 );
				exit();
			}

		}
  }
}
add_action( 'template_redirect', 'l2wpr_redirect' );
