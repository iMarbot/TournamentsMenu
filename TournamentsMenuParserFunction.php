<?php

class TournamentsMenuParserFunction {

	public static function onParserFirstCallInit( $parser ) {
		$parser->setHook( 'tournaments', 'TournamentsMenuParserFunction::getTournamentsList' );
	}

	public static function getTournamentsList( $innertext, $params, $parser, $frame ) {
		global $wgOut, $wgCommandLineMode;
		if ( isset( $params[ 'page' ] ) && !empty( $params[ 'page' ] ) ) {
			$message = htmlspecialchars( $params[ 'page' ] );
		} else {
			$message = 'Tournaments';
		}
		if ( isset( $params[ 'filter' ] ) && !empty( $params[ 'filter' ] ) ) {
			$filters = explode( ',', htmlspecialchars( $params[ 'filter' ] ) );
		}
		$iconTemplatePrefix = 'LeagueIconSmall';
		$return = '';

		$titleFromText = Title::newFromText( $message, NS_PROJECT );
		if ( $titleFromText->exists() ) {
			$wikipage = WikiPage::factory( $titleFromText );
			$revision = $wikipage->getRevision();
			if ( !$revision ) {
				return '';
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
								$startDate = trim( explode( '=', $value )[ 1 ] );
								unset( $line[ $key ] );
							} else if ( strpos( $value, 'enddate' ) === 0 ) {
								$endDate = trim( explode( '=', $value )[ 1 ] );
								unset( $line[ $key ] );
							} else if ( strpos( $value, 'icon' ) === 0 ) {
								$icon = trim( explode( '=', $value )[ 1 ] );
								unset( $line[ $key ] );
							} else if ( strpos( $value, 'filter' ) === 0 ) {
								$filter = trim( explode( '=', $value )[ 1 ] );
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
						if ( $link == '-' )
							continue;

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

						if ( $title == null ) {
							$exists = false;
						} else {
							$exists = $title->exists();
						}

						$text = htmlspecialchars( $text );

						$item = array(
							'text' => $text,
							'href' => $href,
							'id' => 'n-' . strtr( $line[ 1 ], ' ', '-' ) . '-mainpage',
							'active' => false,
							'exists' => $exists
						);

						if ( isset( $startDate ) && !empty( trim( $startDate ) ) ) {
							$item[ 'startdate' ] = htmlspecialchars( $startDate );
						}
						if ( isset( $endDate ) && !empty( trim( $endDate ) ) ) {
							$item[ 'enddate' ] = htmlspecialchars( $endDate );
						}
						if ( isset( $icon ) && !empty( trim( $icon ) ) ) {
							$item[ 'icon' ] = htmlspecialchars( $icon );
						}
						if ( isset( $filter ) && !empty( trim( $filter ) ) ) {
							$item[ 'filter' ] = htmlspecialchars( $filter );
						}

						$new_bar[ $heading ][] = $item;
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

						if ( $title == null ) {
							$exists = false;
						} else {
							$exists = $title->exists();
						}

						$new_bar[ $heading ][] = array(
							'text' => $text,
							'href' => $href,
							'id' => 'n-' . strtr( $line, ' ', '-' ),
							'active' => false,
							'exists' => $exists
						);
					}
				}
			}

			if ( isset( $filters ) && count( $filters ) > 1 ) {
				$return .= '<form>';
				$helperA = '';
				$helperB = '';
				foreach ( $filters as $filter ) {
					$helperA .= '<input type="checkbox" id="tournaments-list-filter-' . $filter . '">';
					$helperB .= '<label for="tournaments-list-filter-' . $filter . '"></label>';
				}
				$return .= $helperA . $helperB;
				unset( $helperA, $helperB );
			}
			$return .= '<ul class="tournaments-list">';
			foreach ( $new_bar as $type_name => $type_list ) {
				$return .= '<li>';
				$return .= '<span class="tournaments-list-heading">' . $type_name . '</span>';
				$return .= '<ul class="tournaments-list-type-list">';
				foreach ( $type_list as $tournament_arr ) {
					$return .= '<li' . ( isset( $tournament_arr[ 'filter' ] ) ? ' class="filter-' . $tournament_arr[ 'filter' ] . '"' : '' ) . '>';
					$return .= '<a ' . ( (!$tournament_arr[ 'exists' ] ) ? 'class="new" ' : '' ) . 'href="' . $tournament_arr[ 'href' ] . '">';
					$return .= '<span class="tournaments-list-name">';
					if ( isset( $tournament_arr[ 'icon' ] ) ) {
						$iconTitle = Title::newFromText( $iconTemplatePrefix . '/' . $tournament_arr[ 'icon' ], NS_TEMPLATE );
					} else {
						$iconTitle = null;
					}
					if ( isset( $tournament_arr[ 'icon' ] ) && ( $iconTitle != null ) && ( $iconTitle->exists() ) && ( $parser->getTitle() != null ) ) {
						if ( !$wgCommandLineMode ) {
							$iconHTML = $wgOut->parseInline( '{{' . $iconTemplatePrefix . '/' . $tournament_arr[ 'icon' ] . '|link=}}', false );
							if ( strpos( $iconHTML, 'mw-parser-output' ) !== false ) {
								$iconHTML = substr( $iconHTML, strlen( '<div class="mw-parser-output">' ), -strlen( '</div>' ) );
							}
							$return .= $iconHTML;
						}
					}
					$return .= $tournament_arr[ 'text' ] . '</span>';
					$return .= '<small class="tournaments-list-dates">';
					if ( isset( $tournament_arr[ 'startdate' ] ) && isset( $tournament_arr[ 'enddate' ] ) ) {
						$return .= $tournament_arr[ 'startdate' ];
						$return .= ' &ndash; ';
						if ( substr( $tournament_arr[ 'startdate' ], 0, 3 ) == substr( $tournament_arr[ 'enddate' ], 0, 3 ) ) {
							$tournament_arr[ 'enddate' ] = substr( $tournament_arr[ 'enddate' ], 4 );
						}
						$return .= $tournament_arr[ 'enddate' ];
					} else if ( isset( $tournament_arr[ 'startdate' ] ) ) {
						$return .= $tournament_arr[ 'startdate' ];
					} else if ( isset( $tournament_arr[ 'enddate' ] ) ) {
						$return .= $tournament_arr[ 'enddate' ];
					}
					$return .= '</small>';
					$return .= '</a>';
					$return .= '</li>';
				}
				$return .= '</ul>';
				$return .= '</li>';
			}
			$return .= '</ul>';
			if ( isset( $filters ) && count( $filters ) > 1 ) {
				$return .= '</form>';
			}
		} else {
			global $wgMetaNamespace;
			$return .= '<span class="error">' . wfMessage( 'tournamentsmenu-page-not-existing' )->params( $wgMetaNamespace . ':' . str_replace( $wgMetaNamespace . ':', '', $message ) )->text() . '</span>';
		}
		return array( trim( $return ), 'markerType' => 'nowiki' );
	}

}
