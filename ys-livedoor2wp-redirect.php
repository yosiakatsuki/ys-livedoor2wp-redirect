<?php
/**
 * Plugin Name:     YS Livedoor2WP Redirect
 * Description:     ライブドアブログ時代のURLを上手いことWordPress時代のURLにリダイレクトするプラグイン（暫定的に）
 * Author:          yosiakatsuki
 * Author URI:      https://yosiakatsuki.net/
 * Version:         1.0.0
 *
 * @package         ys-livedoor2wp-redirect
 */

/*
	Copyright (c) 2017 Yoshiaki Ogata (https://yosiakatsuki.net/)
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
function l2wpr_template_redirect() {
	// 404の時にライブドアブログの時のURLか判断してリダイレクトかける
  if( is_404() ){

		$request_url = $_SERVER["REQUEST_URI"];

		// パーマリンク設定に.htmlを入れるとライブドアブログ時代以外でも処理してしまうので注意
		if ( strpos( $request_url, '.html' ) !== FALSE ){

      for ($i=1; $i <=5 ; $i++) {
  			$temp = substr( $request_url, strrpos( $request_url, '/' ) + 1);
  			$temp = str_replace( '.html', '', $temp );
  			$temp = preg_replace( '/\?.*$/i', '', $temp );

				//月別アーカイブ
				if( 1 === preg_match( '/\d\d\d\d-\d\d/i', $temp ) ) {
					l2wpr_redirect_date( $temp );
				}


        if( $i > 1 ){
          $temp .= '-' . $i;
        }
  			$post = get_page_by_path( $temp, OBJECT, 'post' );

  			if( $post ){
          l2wpr_redirect( $post );
  			}
      }
		}
    //RDF
		if ( strpos( $request_url, 'index.rdf' ) !== FALSE ){
      l2wpr_redirect_rss();
    }
  }
}
add_action( 'template_redirect', 'l2wpr_template_redirect' );


function l2wpr_redirect( $post ) {
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

function l2wpr_redirect_rss() {

  wp_safe_redirect( get_bloginfo('rss_url'), 301 );
  exit();
}

function l2wpr_redirect_date( $temp ) {

  wp_safe_redirect( home_url( '/' ) . str_replace( '-', '/', $temp ) , 301 );
  exit();
}



function l2wpr_admin_menu() {
	add_options_page(
		'ライブドアブログ->WP',
		'ライブドアブログ->WP',
		'manage_options',
		'ys-livedoor2wp-redirect',
		'l2wpr_options_page'
	);
}
add_action( 'admin_menu', 'l2wpr_admin_menu' );

function l2wpr_options_page() {
if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'あなたはこのページへアクセスする権限がありません！！！！' );
	}
	?>

	<div class="wrap">
		<h2>最近のリダイレクト履歴(簡易版)</h2>
		<div id="poststuff" class="l2wpr_options_page">

			<?php
				$arg = array(
								'meta_key'     => 'l2wpr_redirect_last_time',
								'meta_value'   => '1900/01/01 00:00:00',
								'meta_compare' => '>',
								'order'        => 'DESC',
								'orderby'      => 'meta_value'
							);
				$posts = get_posts($arg);
				if( $posts ): ?>
					<table>
						<tbody>
							<tr>
								<th class="last-time">最終リダイレクト</th>
								<th class="count">累計回数</th>
								<th class="title">タイトル</th>
							</tr>
					<?php
						foreach ($posts as $post) :
							$post_id = $post->ID;
							?>
							<tr>
								<td><?php echo esc_html( get_post_meta( $post_id, 'l2wpr_redirect_last_time', true ) ); ?></td>
								<td><?php echo esc_html( get_post_meta( $post_id, 'l2wpr_redirect_cnt', true ) ); ?></td>
								<td><?php echo esc_html( get_the_title( $post_id ) ); ?></td>
							</tr>
					<?php
						endforeach; ?>
					</tbody>
				</table>
			<?php
				endif;
			 ?>
		</div>
	</div><!-- /.warp -->
	<style>
	.l2wpr_options_page table {
		width: 100%;
		table-layout: fixed;
		border-top: 1px solid #333;
		border-left: 1px solid #333;
		box-sizing: border-box;
		padding: 0;
		margin: 0;
		    border-spacing:0;
	}
		.l2wpr_options_page th ,
		.l2wpr_options_page td {
			padding: .5em;
			margin: 0;
			border-right: 1px solid #333;
			border-bottom: 1px solid #333;
			box-sizing: border-box;
		}
		.l2wpr_options_page table .last-time {
			width: 200px;
		}
		.l2wpr_options_page table .count {
			width: 100px;
		}
	</style>
<?php
}
