<?php
/**
 * Showing Charts with charts.js
 *
 * This class shows charts by charts.js
 *
 * @author awesome.ug, Author <support@awesome.ug>
 * @package Questions/Core
 * @version 1.0.0
 * @since 1.0.0
 * @license GPL 2

Copyright 2015 awesome.ug (support@awesome.ug)

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

if ( !defined( 'ABSPATH' ) ) exit;

class Questions_ChartCreator_Dimple extends Questions_ChartCreator{
    /**
     * Initializes the Component.
     * @since 1.0.0
     */
    public function __construct() {
        parent::__construct(
            'Dimple chart creator',
            'Creates charts with D3 Dimple library',
            'dimple'
        );
    } // end constructor

    /**
     * Showing bars
     * @param string $title Title
     * @param array $answers
     * @param array $attr
     * @return mixed
     */
    public static function show_bars( $title, $answers, $attr = array() ){
        $atts = array();

        $defaults = array(
            'id' => 'dimple' . md5( rand() ),
            'width' => '100%',
            'height' => '100%',
            'title_tag' => 'h3',
        );
        $atts = wp_parse_args( $defaults, $atts );

        $id = $atts[ 'id' ];
        $width = $atts[ 'width' ];
        $height = $atts[ 'height' ];

        $answer_text = __( 'Answer', 'questions-locale' );
        $value_text = __( 'Votes', 'questions-locale' );

        $data = self::prepare_data( $answers, $answer_text, $value_text );

        $js = 'var svg = dimple.newSvg("#' . $id . '", "' . $width . '", "' . $height . '"  ), data = [ ' . $data . ' ], chart=null, x=null;';
        $js.= 'chart = new dimple.chart( svg, data );';

        $js.= 'x = chart.addCategoryAxis("x", "' . $answer_text . '");';
        $js.= 'y = chart.addMeasureAxis("y", "' . $value_text . '");';

        $js.= 'x.fontSize = "0.8em";';
        // $js.= 'x.hidden = true;';

        $js.= 'y.fontSize = "0.8em";';
        $js.= 'y.showGridlines = false;';
        $js.= 'y.ticks = 2.5;';

        $js.= 'x.floatingBarWidth = 5;';

        $js.= 'var bars = chart.addSeries([ "' . $value_text . '", "'  . $answer_text . '" ], dimple.plot.bar);';
        // $js.= 'chart.addLegend(60, 475, 500	, 600, "left", [ bars ]);';

        $js.= 'chart.draw();';

        $js.= 'x.titleShape.text("' . $title . '");';
        $js.= 'x.titleShape.style( "font-size", "14px");';
        $js.= 'x.titleShape.style( "font-weight", "bold");';
        $js.= 'x.titleShape.style( "padding-top", "30px");';

        // $js.= 'var height = document.getElementById("' . $id . '").getBBox().height;';

        $js.= 'jQuery( function ($) { ';
        $js.= 'var gcontainer = $( "#' . $id . ' g" );';
        $js.= 'var grect = gcontainer[0].getBoundingClientRect();';
        $js.= '$( "#' . $id . '" ).width( grect.width );';
        $js.= '$( "#' . $id . '" ).height( grect.height );';
        $js.= '});';

        $html = '<div id="' . $id . '" class="questions-dimplechart">';
        $html.= '<script type="text/javascript">';
        $html.= $js;
        $html.= '</script>';
        $html.= '<div style="clear:both;"></div></div>';

        return $html;
    }

    /**
     * Preparing data for Dimple
     * @param $answers
     * @param $answer_text
     * @param $value_text
     * @return string
     */
    private static function prepare_data( $answers, $answer_text, $value_text ){
        $rows = array();

        foreach( $answers AS $label => $value ):
            $rows[] = '{"' . $answer_text . '" : "' . $label . '", "' . $value_text . '" : ' . $value. '}';
        endforeach;

        $data = implode( ',', $rows );

        return $data;
    }

    /**
     * Loading Scripts
     */
    public function load_scripts(){
        wp_enqueue_script( 'questions-d3-js',  QUESTIONS_URLPATH . '/components/charts/dimple/lib/d3.min.js' );
        wp_enqueue_script( 'questions-dimple-js',  QUESTIONS_URLPATH . '/components/charts/dimple/lib/dimple.v2.1.2.min.js' );
    }
}
qu_register_chart_creator( 'Questions_ChartCreator_Dimple' );