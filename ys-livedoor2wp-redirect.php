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

/**
 * 404の時にライブドアブログの時のURLか判断してリダイレクトかける
 */
function ys_l2wpr_template_redirect() {
	if ( is_404() ) {
		$request_url = $_SERVER["REQUEST_URI"];
		/**
		 * パーマリンク設定に.htmlを入れるとライブドアブログ時代以外でも処理してしまうので注意
         * ライブドアブログのパーマリンク設定に.htmlが無いと判断できない
		 */
		$livedoor_url_pattern = apply_filters( 'ys_l2wpr_livedoor_url_pattern', '.html' );
		if ( strpos( $request_url, $livedoor_url_pattern ) !== false ) {
			/**
			 * 「-2」等が付いたURLも処理したいので3回くらい頑張る
			 */
			$search_slug = ys_l2wpr_get_search_slug( $request_url );
			$retry_count = apply_filters( 'ys_l2wpr_retry_count', 3 );
			for ( $i = 1; $i <= $retry_count; $i ++ ) {
				/**
				 * 月別アーカイブを検証
				 */
				if ( ys_l2wpr_redirect_date( $search_slug, $request_url ) ) {
					return;
				}
				/**
				 * 2回目以降は「-2」をつける
				 */
				if ( $i > 1 ) {
					$search_slug .= '-' . $i;
				}
				/**
				 * スラッグから投稿を取得し、リダイレクト
				 */
				if ( ys_l2wpr_redirect( $search_slug ) ) {
					return;
				}
			}
		}
		/**
		 * RDF検証
		 */
		if ( strpos( $request_url, 'index.rdf' ) !== false ) {
			ys_l2wpr_redirect_rss();
		}
	}
}

add_action( 'template_redirect', 'ys_l2wpr_template_redirect' );

/**
 * 検索URL取得
 *
 * @param string $request_url リクエストURL.
 *
 * @return string
 */
function ys_l2wpr_get_search_slug( $request_url ) {
	$search_slug = substr( $request_url, strrpos( $request_url, '/' ) + 1 );
	/**
	 * .html,archives/を削除
	 */
	$search_slug = str_replace( '.html', '', $search_slug );
	$search_slug = str_replace( 'archives/', '', $search_slug );
	$search_slug = apply_filters( 'ys_l2wpr_search_url_before_del_query_str', $search_slug, $request_url );
	/**
	 * クエリストリングを削除
	 */
	$search_slug = preg_replace( '/\?.*$/i', '', $search_slug );

	return apply_filters( 'ys_l2wpr_search_url', $search_slug, $request_url );
}

/**
 * リダイレクト処理
 *
 * @param string $search_slug スラッグ.
 * @return boolean
 */
function ys_l2wpr_redirect( $search_slug ) {

	/**
	 * スラッグから投稿を取得
	 */
	$post = get_page_by_path( $search_slug, OBJECT, 'post' );
	/**
	 * 投稿を取得できなければ終了
	 */
	if ( is_null( $post ) ) {
		return false;
	}
	/**
	 * リダイレクト先URLを取得
	 */
	$post_id      = $post->ID;
	$redirect_url = get_permalink( $post_id );
	/**
	 * リダイレクト実績
	 */
	$key_hit = 'ys_l2wpr_redirect_cnt';
	$val_hit = get_post_meta( $post_id, $key_hit, true );
	if ( ! $val_hit || ! is_numeric( $val_hit ) ) {
		$val_hit = 0;
	} else {
		$val_hit = (int) $val_hit;
	}
	$val_hit ++;
	/**
	 * リダイレクト実績更新
	 */
	update_post_meta( $post_id, $key_hit, $val_hit );
	update_post_meta( $post_id, 'ys_l2wpr_redirect_last_time', date_i18n( 'Y/m/d H:i:s' ) );
	/**
	 * リダイレクト実行
	 */
	wp_safe_redirect( $redirect_url, 301 );
	exit();
}

/**
 * RSSのリダイレクト
 */
function ys_l2wpr_redirect_rss() {

	wp_safe_redirect( get_bloginfo( 'rss_url' ), 301 );
	exit();
}

/**
 * 日別アーカイブへのリダイレクト
 *
 * @param string $search_slug  検証URL.
 * @param string $request_url リクエストURL.
 *
 * @return bool
 */
function ys_l2wpr_redirect_date( $search_slug, $request_url ) {

	$month_archive_pattern = apply_filters( 'ys_l2wpr_month_archive_pattern', '/\d\d\d\d-\d\d/i', $search_slug, $request_url );
	/**
	 * 日付アーカイブの検証
	 */
	if ( 1 === preg_match( $month_archive_pattern, $search_slug ) ) {
		wp_safe_redirect( home_url( '/' ) . str_replace( '-', '/', $search_slug ), 301 );
		exit();
	}
	return false;
}


function ys_l2wpr_admin_menu() {
	add_options_page(
		'ライブドアブログ->WP',
		'ライブドアブログ->WP',
		'manage_options',
		'ys-livedoor2wp-redirect',
		'ys_l2wpr_options_page'
	);
}

add_action( 'admin_menu', 'ys_l2wpr_admin_menu' );

function ys_l2wpr_options_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'あなたはこのページへアクセスする権限がありません！！！！' );
	}
	?>

    <div class="wrap">
        <h2>最近のリダイレクト履歴(簡易版)</h2>
        <div id="poststuff" class="ys_l2wpr_options_page">

			<?php
			$arg   = array(
				'meta_key'     => 'ys_l2wpr_redirect_last_time',
				'meta_value'   => '1900/01/01 00:00:00',
				'meta_compare' => '>',
				'order'        => 'DESC',
				'orderby'      => 'meta_value'
			);
			$posts = get_posts( $arg );
			if ( $posts ): ?>
                <table>
                    <tbody>
                    <tr>
                        <th class="last-time">最終リダイレクト</th>
                        <th class="count">累計回数</th>
                        <th class="title">タイトル</th>
                    </tr>
					<?php
					foreach ( $posts as $post ) :
						$post_id = $post->ID;
						?>
                        <tr>
                            <td><?php echo esc_html( get_post_meta( $post_id, 'ys_l2wpr_redirect_last_time', true ) ); ?></td>
                            <td><?php echo esc_html( get_post_meta( $post_id, 'ys_l2wpr_redirect_cnt', true ) ); ?></td>
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
        .ys_l2wpr_options_page table {
            width: 100%;
            table-layout: fixed;
            border-top: 1px solid #333;
            border-left: 1px solid #333;
            box-sizing: border-box;
            padding: 0;
            margin: 0;
            border-spacing: 0;
        }

        .ys_l2wpr_options_page th,
        .ys_l2wpr_options_page td {
            padding: .5em;
            margin: 0;
            border-right: 1px solid #333;
            border-bottom: 1px solid #333;
            box-sizing: border-box;
        }

        .ys_l2wpr_options_page table .last-time {
            width: 200px;
        }

        .ys_l2wpr_options_page table .count {
            width: 100px;
        }
    </style>
	<?php
}
