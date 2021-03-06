<?php

class TournamentsMenuHooks {

	public static function onSkinBuildSidebar( $skin, &$bar ) {
		global $wgOut, $wgCommandLineMode;
		if ( isset( $bar[ 'TOURNAMENTS' ] ) ) {
			$message = 'Tournaments';
			$iconTemplatePrefix = 'LeagueIconSmall';

			$titleFromText = Title::newFromText( $message, NS_PROJECT );
			if ( $titleFromText->exists() ) {
				$wikipage = WikiPage::factory( $titleFromText );
				$revision = $wikipage->getRevision();
				if ( !$revision ) {
					return true;
				}
				$content = $revision->getContent( Revision::FOR_PUBLIC );
				$text = ContentHandler::getContentText( $content );
				$lines = explode( "\n", $text );

				$new_bar = array();
				$heading = '';
				foreach ( $lines as $line ) {
					if ( strpos( $line, '*' ) !== 0 ) {
						continue;
					} else if ( strpos( $line, '**' ) !== 0 ) {
						$line = trim( $line, '* ' );
						$heading = htmlspecialchars( $line );
						if ( !array_key_exists( $heading, $new_bar ) ) {
							$new_bar[ $heading ] = array();
						}
					} else {
						if ( strpos( $line, '|' ) !== false ) { // sanity check
							$line = array_map( 'trim', explode( '|', trim( $line, '* ' ) ) );

							foreach ( $line as $key => $value ) {
								$value = trim( $value );
								if ( strpos( $value, 'startdate' ) === 0 ) {
									if ( !empty( trim( explode( '=', $value )[ 1 ] ) ) ) {
										$startDate = htmlspecialchars( trim( explode( '=', $value )[ 1 ] ) );
									}
									unset( $line[ $key ] );
								} else if ( strpos( $value, 'enddate' ) === 0 ) {
									if ( !empty( trim( explode( '=', $value )[ 1 ] ) ) ) {
										$endDate = htmlspecialchars( trim( explode( '=', $value )[ 1 ] ) );
									}
									unset( $line[ $key ] );
								} else if ( strpos( $value, 'icon' ) === 0 ) {
									if ( !empty( trim( explode( '=', $value )[ 1 ] ) ) ) {
										$icon = htmlspecialchars( trim( explode( '=', $value )[ 1 ] ) );
									}
									unset( $line[ $key ] );
								} else if ( strpos( $value, 'filter' ) === 0 ) {
									if ( !empty( trim( explode( '=', $value )[ 1 ] ) ) ) {
										$filter = htmlspecialchars( trim( explode( '=', $value )[ 1 ] ) );
									}
									unset( $line[ $key ] );
								}
							}
							$line = array_values( $line );
							if ( count( $line ) == 1 ) {
								$line[ 1 ] = $line[ 0 ];
							}

							if ( $line[ 0 ] == null ) {
								$link = '-';
							} else {
								$link = wfMessage( $line[ 0 ] )->inContentLanguage()->text();
							}
							if ( $link == '-' ) {
								continue;
							}

							$text = wfMessage( $line[ 1 ] )->text();
							if ( wfMessage( $line[ 1 ], $text )->inContentLanguage()->isBlank() ) {
								$text = $line[ 1 ];
							}
							if ( wfMessage( $line[ 0 ], $link )->inContentLanguage()->isBlank() ) {
								$link = $line[ 0 ];
							}

							if ( preg_match( '/^(?:' . wfUrlProtocols() . ')/', $link ) ) {
								$href = $link;
							} else {
								$title = Title::newFromText( $link );
								if ( $title ) {
									$title = $title->fixSpecialName();
									$href = $title->getLocalURL();
								} else {
									$href = 'INVALID-TITLE';
								}
							}

							$text = htmlspecialchars( $text );

							/*
							  if( isset( $startDate ) || isset( $endDate ) ) {
							  $text .= ' <small>(';
							  if( isset( $startDate ) ) {
							  $text .= $startDate;
							  }
							  if( isset( $startDate ) && isset( $endDate ) ) {
							  $text .= ' - ';
							  }
							  if( isset( $endDate ) ) {
							  if( substr( $startDate, 0, 3 ) == substr( $endDate, 0, 3 ) ) {
							  $endDate = substr( $endDate, 4 );
							  }
							  $text .= $endDate;
							  }
							  $text .= ')</small>';
							  }
							 */

							if ( isset( $icon ) ) {
								$iconTitle = Title::newFromText( $iconTemplatePrefix . '/' . $icon, NS_TEMPLATE );
								if ( ( $iconTitle != null ) && ( $iconTitle->exists() ) && ( $skin->getTitle() != null ) ) {
									if ( !$wgCommandLineMode ) {
										$iconHTML = $wgOut->parseInline( '{{' . $iconTemplatePrefix . '/' . $icon . '|link=}}', false );
										if ( strpos( $iconHTML, 'mw-parser-output' ) !== false ) {
											$iconHTML = substr( $iconHTML, strlen( '<div class="mw-parser-output">' ), -strlen( '</div>' ) );
										}
										$text = $iconHTML . ' ' . $text;
									}
								}
							}

							$new_bar[ $heading ][] = array(
								'text' => $text,
								'href' => $href,
								'id' => 'n-' . strtr( $line[ 1 ], ' ', '-' ),
								'active' => false
							);
							unset( $startDate, $endDate, $icon, $filter );
						} else {
							$line = trim( $line, '* ' );
							//$link = wfMsgForContent( $line );
							//if($link == '-')
							//	continue;

							$text = htmlspecialchars( $line );
							$link = $line;
							$title = Title::newFromText( $link );
							if ( $title ) {
								$title = $title->fixSpecialName();
								$href = $title->getLocalURL();
							} else {
								$href = 'INVALID-TITLE';
							}
							$new_bar[ $heading ][] = array(
								'text' => $text,
								'href' => $href,
								'id' => 'n-' . strtr( $line, ' ', '-' ),
								'active' => false
							);
						}
					}
				}
				$bar[ 'TOURNAMENTS' ] = $new_bar;
			}
		}
		return true;
	}

}
