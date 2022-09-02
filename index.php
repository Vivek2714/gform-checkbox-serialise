<?php
/*
  Plugin name: Gravity Form checkbox serialized
  Description: Send checkboxes as serialized format in webhook request
  Author: Vivek.
*/

class gformCheckboxSerialized{

  public function __construct(){

    ## CHange webhook request data for xplan
    add_filter( 'gform_webhooks_request_args', [ $this, 'changeRequestData'], 10, 4 );

    ## Adding custom setting option for xplan web request
    add_filter( 'gform_gravityformswebhooks_feed_settings_fields', [ $this, 'addCheckboxSerializedOption' ], 10, 2 );
  
  }

  ## Add a custom setting field in webhook feed
  public function addCheckboxSerializedOption( $feed_settings_fields, $addon ) {

    $feed_settings_fields[0]['fields'][] = array(
      'label'          => esc_html__( 'Do you want to enable checkbox serialize?', 'gravityformswebhooks' ),
      'name'           => '_serialized_chechbox_values',
      'type'           => 'radio',
      'default_value'  => 'no',
      'horizontal'     => true,
      'required'       => true,
      'onchange'       => "jQuery(this).closest('form').submit();",
      'tooltip'        => sprintf(
        '<h6>%s</h6>%s',
         esc_html__( 'Do you want to enable checkbox serialize?', 'gravityformswebhooks' ),
         esc_html__( 'If you want to send serialized data for checkbox then select yes option.', 'gravityformswebhooks' )
      ),
      'choices' => array(
        array(
          'label' => esc_html__( 'Yes', 'gravityformswebhooks' ),
          'value' => 'yes',
        ),
        array(
          'label' => esc_html__( 'No', 'gravityformswebhooks' ),
          'value' => 'no',
        ),
      ),
    );

    return $feed_settings_fields;
  }

  ## Modify checkbox values
  public function modifyData( $entry = [], $type = "all_fields", $fieldValues = [], $formId ){
    if( empty($entry) ){
      return [];
    }
    $tempEntry = [];
    if( $type == "all_fields" ){
      foreach( $entry as $key => $value ){
        if( strpos( $key, "." ) ){
          $fieldID = explode( ".", $key );
          ## Get field object
          $field = GFAPI::get_field( $formId, $fieldID[0] );
          if( $field->type != "checkbox"){
            $tempEntry[ $key ] = $value;
            continue;
          }
          if( empty($value) ){
            continue;
          }
          $tempEntry[ $fieldID[0] ][] = $value;
          continue;
        }
        $tempEntry[ $key ] = $value;
      }
    }else{
      foreach( $fieldValues as $fieldData ){
        if( !empty($fieldData['custom_value']) ){
          $tempEntry[ $fieldData['custom_key'] ] = $fieldData['custom_value'];
          continue;
        }
        ## Get field object
        $field = GFAPI::get_field( $formId, $fieldData['value'] );
        if( $field->type == "checkbox"){
          $tempEntry[ $fieldData['custom_key'] ] = explode( ",", $entry[ $fieldData['custom_key'] ] );
          continue;
        }
        $tempEntry[ $fieldData['custom_key'] ] = $entry[ $fieldData['custom_key'] ];
      }
    }
    return $tempEntry;
  }

  ## Change request data
  public function changeRequestData( $request_args, $feed, $entry, $form ){

    $feedMeta = $feed['meta'];

    // Check if xplan mode is enabled, if not enable then proceed with default request data
    if( $feedMeta['_serialized_chechbox_values'] != 'yes' ){
      return $request_args;
    }

    $body = $request_args['body'];
    if( !is_array($body) ){
      $entryArray = json_decode( $body, true );
      $request_args['body'] = json_encode( $this->modifyData( $entryArray, $feedMeta['requestBodyType'], $feedMeta['fieldValues'], $form['id'] ) );
    }else{
      $request_args['body'] = $this->modifyData( $body, $feedMeta['requestBodyType'], $feedMeta['fieldValues'], $form['id'] ) ;
    }

    return $request_args;
  }

}

add_action( 'plugins_loaded', function(){
  new gformCheckboxSerialized();
});
?>