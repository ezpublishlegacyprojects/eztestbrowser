<?php

class ezpController
{
  var $headerList;
  var $scriptStartTime;
  var $warningList = array();
  var $access;
  var $tempalte_result = null;

  public function __destruct()
  {
    unset($GLOBALS['eZURIRequestInstance']);
    unset($GLOBALS['eZRequestedModule']);
    unset($GLOBALS['eZRedirection']);
    unset($GLOBALS['eZDebugAllowed']);
    unset($GLOBALS['eZSiteBasics']);
    unset($GLOBALS['eZGlobalRequestURI']);
    unset($GLOBALS['eZRequestedURI']);
    unset($GLOBALS['EZCODEPAGEPERMISSIONS']);
    unset($GLOBALS['eZCustomPageLayout']);
    unset($GLOBALS['eZDebugWarning']);
    unset($GLOBALS['eZDebugError']);

  }

  private function initializeCodePagePermissions()
  {
    list( $this->iniFilePermission, $this->iniDirPermission ) = $this->ini->variableMulti( 'FileSettings', array( 'StorageFilePermissions', 'StorageDirPermissions' ) );

    // OPTIMIZATION:
    // Sets permission array as global variable, this avoids the eZCodePage include
    $GLOBALS['EZCODEPAGEPERMISSIONS'] = array( 'file_permission' => octdec( $this->iniFilePermission ),
                                               'dir_permission'  => octdec( $this->iniDirPermission ),
                                               'var_directory'   => eZSys::cacheDirectory() );
  }

  private function initializeSiteBasics()
  {
    $this->siteBasics = array();
    $this->siteBasics['external-css'] =& $this->use_external_css;
    $this->siteBasics['show-page-layout'] =& $this->show_page_layout;
    $this->siteBasics['module-run-required'] =& $this->moduleRunRequired;
    $this->siteBasics['policy-check-required'] =& $this->policyCheckRequired;
    $this->siteBasics['policy-check-omit-list'] =& $this->policyCheckOmitList;
    $this->siteBasics['url-translator-allowed'] =& $this->urlTranslatorAllowed;
    $this->siteBasics['validity-check-required'] =& $this->validityCheckRequired;
    $this->siteBasics['user-object-required'] =& $this->userObjectRequired;
    $this->siteBasics['session-required'] =& $this->sessionRequired;
    $this->siteBasics['db-required'] =& $this->dbRequired;
    $this->siteBasics['no-cache-adviced'] =& $this->noCacheAdviced;
    $this->siteBasics['site-design-override'] =& $this->siteDesignOverride;
    $this->siteBasics['module-repositories'] =& $this->moduleRepositories;
  }

  public static function fetchModule( $uri, $check, &$module, &$module_name, &$function_name, &$params )
  {
      $module_name = $uri->element();
      if ( $check !== null and isset( $check["module"] ) )
          $module_name = $check["module"];

      // Try to fetch the module object
      $module = eZModule::exists( $module_name );
      if ( !( $module instanceof eZModule ) )
      {
          return false;
      }

      $uri->increase();
      $function_name = "";
      if ( !$module->singleFunction() )
      {
          $function_name = $uri->element();
          $uri->increase();
      }
      // Override it if required
      if ( $check !== null and isset( $check["function"] ) )
          $function_name = $check["function"];

      $params = $uri->elements( false );
      return true;
  }

  /*!
  \private
  */
  public static function eZDisplayResult( $templateResult )
  {
      $output = null;
      if ( $templateResult !== null )
      {
          $classname = eZINI::instance()->variable( "OutputSettings", "OutputFilterName" );
          if( !empty( $classname ) && class_exists( $classname ) )
          {
              $templateResult = call_user_func( array ( $classname, 'filter' ), $templateResult );
          }
          $debugMarker = '<!--DEBUG_REPORT-->';
          $pos = strpos( $templateResult, $debugMarker );
          if ( $pos !== false )
          {
              $debugMarkerLength = strlen( $debugMarker );
              $output = substr( $templateResult, 0, $pos );
              $output .= substr( $templateResult, $pos + $debugMarkerLength );
          }
          else
          {
              $output = $templateResult;
          }
      }
      return $output;
  }

  

  /*!
   Appends a new warning item to the warning list.
   \a $parameters must contain a \c error and \c text key.
  */
  public function eZAppendWarningItem( $parameters = array() )
  {
      $parameters = array_merge( array( 'error' => false,
                                        'text' => false,
                                        'identifier' => false ),
                                 $parameters );
      $error = $parameters['error'];
      $text = $parameters['text'];
      $identifier = $parameters['identifier'];
      $this->warningList[] = array( 'error' => $error,
                              'text' => $text,
                              'identifier' => $identifier );
  }

  public static function eZFatalError()
  {
    //eZDebug::setHandleType( eZDebug::HANDLE_NONE );
    print( "<b>Fatal error</b>: eZ Publish did not finish its request<br/>" );
    print( "<p>The execution of eZ Publish was abruptly ended, the debug output is present below.</p>" );
    $templateResult = null;
    self::eZDisplayResult( $templateResult );
  }
  
  public static function eZDBCleanup()
  {
      if ( class_exists( 'eZDB' )
           and eZDB::hasInstance() )
      {
          $db = eZDB::instance();
          $db->setIsSQLOutputEnabled( false );
      }
  //     session_write_close();
  }
  /*!
   Reads settings from i18n.ini and passes them to eZTextCodec.
  */
  public function eZUpdateTextCodecSettings()
  {
      $ini = eZINI::instance( 'i18n.ini' );

      list( $i18nSettings['internal-charset'], $i18nSettings['http-charset'], $i18nSettings['mbstring-extension'] ) =
          $ini->variableMulti( 'CharacterSettings', array( 'Charset', 'HTTPCharset', 'MBStringExtension' ), array( false, false, 'enabled' ) );

      eZTextCodec::updateSettings( $i18nSettings );
  }

  /*!
   Reads settings from site.ini and passes them to eZDebug.
  */
  public function eZUpdateDebugSettings()
  {
      $ini = eZINI::instance();

      $settings = array();
      list( $settings['debug-enabled'], $settings['debug-by-ip'], $settings['log-only'], $settings['debug-by-user'], $settings['debug-ip-list'], $logList, $settings['debug-user-list'] ) =
          $ini->variableMulti( 'DebugSettings',
                               array( 'DebugOutput', 'DebugByIP', 'DebugLogOnly', 'DebugByUser', 'DebugIPList', 'AlwaysLog', 'DebugUserIDList' ),
                               array( 'enabled', 'enabled', 'disabled', 'enabled' ) );
      $logMap = array( 'notice' => eZDebug::LEVEL_NOTICE,
                       'warning' => eZDebug::LEVEL_WARNING,
                       'error' => eZDebug::LEVEL_ERROR,
                       'debug' => eZDebug::LEVEL_DEBUG,
                       'strict' => eZDebug::LEVEL_STRICT );
      $settings['always-log'] = array();
      foreach ( $logMap as $name => $level )
      {
          $settings['always-log'][$level] = in_array( $name, $logList );
      }
      eZDebug::updateSettings( $settings );
  }

  private static function checkPHPVersion()
  {
    if ( version_compare( PHP_VERSION, '5.1' ) < 0 )
    {
        print( "<h1>Unsupported PHP version " . PHP_VERSION . "</h1>" );
        print( "<p>eZ Publish 4.x does not run with PHP version lower than 5.1.</p>".
               "<p>For more information about supported software please visit ".
               "<a href=\"http://ez.no/download/ez_publish\" >eZ Publish download page</a></p>" );
        exit;
    }
  }

  private function setTimezone()
  {
    if ( !ini_get( "date.timezone" ) )
    {
        date_default_timezone_set( "UTC" );
    }
  }

  private function setCorrectTimezone()
  {
    // Set correct site timezone
    $timezone = $this->ini->variable( "TimeZoneSettings", "TimeZone");
    if ( $timezone )
    {
      date_default_timezone_set( $timezone );
    }
  }

  private static function ignoreUserAbort()
  {
    ignore_user_abort( true );
  }

  private static function setErrorReporting()
  {
    error_reporting ( E_ALL | E_STRICT );
  }

  private static function addGlobal($name, $value, $by_reference = false)
  {
    if ($by_reference)
    {
      $GLOBALS[$name] =& $value;
      return;
    }

    $GLOBALS[$name] = $value;

  }

  private static function preCheck()
  {
    require_once "pre_check.php";
  }

  private static function checkForExtension()
  {
    require_once( 'kernel/common/ezincludefunctions.php' );
    eZExtension::activateExtensions( 'default' );
  }

  private static function access($uri)
  {
    require_once "access.php";
    $access = accessType( $uri,
                      eZSys::hostname(),
                      eZSys::serverPort(),
                      eZSys::indexFile() );

    $access = changeAccess( $access );
    
    eZDebugSetting::writeDebug( 'kernel-siteaccess', $access, 'current siteaccess' );
    return $access;
  }

  // Functions for session to make sure baskets are cleaned up
  private static function eZSessionBasketDestroy( $db, $key, $escapedKey )
  {
      $basket = eZBasket::fetch( $key );
      if ( is_object( $basket ) )
          $basket->remove();
  }

  private static function eZSessionBasketGarbageCollector( $db, $time )
  {
      eZBasket::cleanupExpired( $time );
  }

  private static function eZSessionBasketEmpty( $db )
  {
      eZBasket::cleanup();
  }

  /* Check if this should be run in a cronjob
   * Need to be runned before eZHTTPTool::instance() because of eZSessionStart() which
   * is called from eZHandlePreChecks() below.
   */
  private static function initializeCallBackForSession($ini)
  {
    if ( !($ini->variable( 'Session', 'BasketCleanup' ) == 'cronjob') )
    {
        // Fill in hooks
        eZSession::addCallback( 'destroy_pre', 'self::eZSessionBasketDestroy');
        eZSession::addCallback( 'gc_pre', 'self::eZSessionBasketGarbageCollector');
        eZSession::addCallback( 'cleanup_pre', 'self::eZSessionBasketCleanup');
    }
  }

  private function initializeDatabaseAndStartSession()
  {
    
    if ( $this->dbRequired )
    {
        $this->db = eZDB::instance();
        if ( $this->sessionRequired and $this->db->isConnected() )
        {
            eZSession::start();
        }

        if ( !$this->db->isConnected() )
        {
            $this->warningList[] = array( 'error' => array( 'type' => 'kernel',
                                                            'number' => eZError::KERNEL_NO_DB_CONNECTION ),
                                                            'text' => 'No database connection could be made, the system might not behave properly.' );
        }
    }
  }

  private function initializeLocale()
  {
    $this->languageCode = eZLocale::instance()->httpLocaleCode();
    $phpLocale = trim( $this->ini->variable( 'RegionalSettings', 'SystemLocale' ) );
    if ( $phpLocale != '' )
    {
        setlocale( LC_ALL, explode( ',', $phpLocale ) );
    }
  }

  private function readRoleSettings()
  {
    $this->policyCheckOmitList = array_merge( $this->policyCheckOmitList, $this->ini->variable( 'RoleSettings', 'PolicyOmitList' ) );

    foreach ( $this->policyCheckOmitList as $omitItem )
    {
        $items = explode( '/', $omitItem );
        if ( count( $items ) > 1 )
        {
            $module = $items[0];
            $view = $items[1];
            if ( !isset( $this->policyCheckViewMap[$module] ) )
                $this->policyCheckViewMap[$module] = array();
            $this->policyCheckViewMap[$module][] = $view;
        }
    }
  }

  private function moduleLoop($check)
  {
    while ( $this->moduleRunRequired )
    {
        $objectHasMovedError = false;
        $objectHasMovedURI = false;
        $this->actualRequestedURI = $this->uri->uriString();

        // Extract user specified parameters
        $userParameters = $this->uri->userParameters();

        // Generate a URI which also includes the user parameters
        $this->completeRequestedURI = $this->uri->originalURIString();

        // Check for URL translation
        if ( $this->urlTranslatorAllowed and
             eZURLAliasML::urlTranslationEnabledByUri( $this->uri ) )
        {
            $translateResult = eZURLAliasML::translate( $this->uri );

            if ( !is_string( $translateResult ) )
            {
                $useWildcardTranslation = $this->ini->variable( 'URLTranslator', 'WildcardTranslation' ) == 'enabled';
                if ( $useWildcardTranslation )
                {
                    $translateResult = eZURLWildcard::translate( $this->uri );
                }
            }

            // Check if the URL has moved
            if ( is_string( $translateResult ) )
            {
                $objectHasMovedURI = $translateResult;
                foreach ( $userParameters as $name => $value )
                {
                    $objectHasMovedURI .= '/(' . $name . ')/' . $value;
                }

                $objectHasMovedError = true;
            }
        }

        if ( $this->uri->isEmpty() )
        {
            $tmp_uri = new eZURI( $this->ini->variable( "SiteSettings", "IndexPage" ) );
            $moduleCheck = accessAllowed( $tmp_uri );
        }
        else
        {
            $moduleCheck = accessAllowed( $this->uri );
        }

        if ( !$moduleCheck['result'] )
        {
            if ( $this->ini->variable( "SiteSettings", "ErrorHandler" ) == "defaultpage" )
            {
                $defaultPage = $this->ini->variable( "SiteSettings", "DefaultPage" );
                $this->uri->setURIString( $defaultPage );
                $moduleCheck['result'] = true;
            }
        }

        $http = eZHTTPTool::instance();

        $displayMissingModule = false;
        $this->oldURI = $this->uri;

        if ( $this->uri->isEmpty() )
        {
            if ( !self::fetchModule( $tmp_uri, $check, $module, $module_name, $function_name, $params ) )
                $displayMissingModule = true;
        }
        else if ( !self::fetchModule( $this->uri, $check, $module, $module_name, $function_name, $params ) )
        {
            if ( $this->ini->variable( "SiteSettings", "ErrorHandler" ) == "defaultpage" )
            {
                $tmp_uri = new eZURI( $this->ini->variable( "SiteSettings", "DefaultPage" ) );
                if ( !self::fetchModule( $tmp_uri, $check, $module, $module_name, $function_name, $params ) )
                    $displayMissingModule = true;
            }
            else
                $displayMissingModule = true;
        }

        if ( !$displayMissingModule &&
             $moduleCheck['result'] &&
             $module instanceof eZModule )
        {
            // Run the module/function
            eZDebug::addTimingPoint( "Module start '" . $module->attribute( 'name' ) . "'" );

            $moduleAccessAllowed = true;
            $omitPolicyCheck = true;
            $runModuleView = true;

            $availableViewsInModule = $module->attribute( 'views' );
            if ( !isset( $availableViewsInModule[$function_name] )
                    && !$objectHasMovedError
                        && !isset( $module->Module['function']['script'] ) )
            {
                $this->moduleResult = $module->handleError( eZError::KERNEL_MODULE_VIEW_NOT_FOUND, 'kernel' );
                $runModuleView = false;
                $this->policyCheckRequired = false;
                $omitPolicyCheck = true;
            }

            if ( $this->policyCheckRequired )
            {
                $omitPolicyCheck = false;
                $moduleName = $module->attribute( 'name' );
                $viewName = $function_name;
                if ( in_array( $moduleName, $this->policyCheckOmitList ) )
                    $omitPolicyCheck = true;
                else if ( isset( $this->policyCheckViewMap[$moduleName] ) and
                          in_array( $viewName, $this->policyCheckViewMap[$moduleName] ) )
                    $omitPolicyCheck = true;
            }
            if ( !$omitPolicyCheck )
            {
                $currentUser = eZUser::currentUser();
                $siteAccessResult = $currentUser->hasAccessTo( 'user', 'login' );

                $hasAccessToSite = false;
                if ( $siteAccessResult[ 'accessWord' ] == 'limited' )
                {
                    $policyChecked = false;
                    foreach ( array_keys( $siteAccessResult['policies'] ) as $key )
                    {
                        $policy = $siteAccessResult['policies'][$key];
                        if ( isset( $policy['SiteAccess'] ) )
                        {
                            $policyChecked = true;
                            $crc32AccessName = eZSys::ezcrc32( $this->access[ 'name' ] );
                            eZDebugSetting::writeDebug( 'kernel-siteaccess', $policy['SiteAccess'], $crc32AccessName );
                            if ( in_array( $crc32AccessName, $policy['SiteAccess'] ) )
                            {
                                $hasAccessToSite = true;
                                break;
                            }
                        }
                        if ( $hasAccessToSite )
                            break;
                    }
                    if ( !$policyChecked )
                        $hasAccessToSite = true;
                }
                else if ( $siteAccessResult[ 'accessWord' ] == 'yes' )
                {
                    eZDebugSetting::writeDebug( 'kernel-siteaccess', "access is yes" );
                    $hasAccessToSite = true;
                }
                else if ( $siteAccessResult['accessWord'] == 'no' )
                {
                    $accessList = $siteAccessResult['accessList'];
                }

                if ( $hasAccessToSite )
                {
                    $accessParams = array();
                    $moduleAccessAllowed = $currentUser->hasAccessToView( $module, $function_name, $accessParams );
                    if ( isset( $accessParams['accessList'] ) )
                    {
                        $accessList = $accessParams['accessList'];
                    }
                }
                else
                {
                    eZDebugSetting::writeDebug( 'kernel-siteaccess', $this->access, 'not able to get access to siteaccess' );
                    $moduleAccessAllowed = false;
                    $requireUserLogin = ( $this->ini->variable( "SiteAccessSettings", "RequireUserLogin" ) == "true" );
                    if ( $requireUserLogin )
                    {
                        $module = eZModule::exists( 'user' );
                        if ( $module instanceof eZModule )
                        {
                            $this->moduleResult = $module->run( 'login', array(),
                                                           array( 'SiteAccessAllowed' => false,
                                                                  'SiteAccessName' => $this->access['name'] ) );
                            $runModuleView = false;
                        }
                    }
                }
            }

            $GLOBALS['eZRequestedModule'] = $module;

            if ( $runModuleView )
            {
                if ( $objectHasMovedError == true )
                {
                    $this->moduleResult = $module->handleError( eZError::KERNEL_MOVED, 'kernel', array( 'new_location' => $objectHasMovedURI ) );
                }
                else if ( !$moduleAccessAllowed )
                {
                    if ( isset( $availableViewsInModule[$function_name][ 'default_navigation_part' ] ) )
                    {
                        $defaultNavigationPart = $availableViewsInModule[$function_name][ 'default_navigation_part' ];
                    }

                    if ( isset( $accessList ) )
                        $this->moduleResult = $module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel', array( 'AccessList' => $accessList ) );
                    else
                        $this->moduleResult = $module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );

                    if ( isset( $defaultNavigationPart ) )
                    {
                        $this->moduleResult['navigation_part'] = $defaultNavigationPart;
                        unset( $defaultNavigationPart );
                    }
                }
                else
                {
                    if ( !isset( $userParameters ) )
                    {
                        $userParameters = false;
                    }

                    // Check if we should switch access mode (http/https) for this module view.
                    eZSSLZone::checkModuleView( $module->attribute( 'name' ), $function_name );

                    $this->moduleResult = $module->run( $function_name, $params, false, $userParameters );

                    if ( $module->exitStatus() == eZModule::STATUS_FAILED and
                         $this->moduleResult == null )
                        $this->moduleResult = $module->handleError( eZError::KERNEL_MODULE_VIEW_NOT_FOUND, 'kernel', array( 'module' => $module_name,
                                                                                                                       'view' => $function_name ) );
                }
            }
        }
        else if ( $moduleCheck['result'] )
        {
            eZDebug::writeError( "Undefined module: $module_name", "index" );
            $module = new eZModule( "", "", $module_name );
            $GLOBALS['eZRequestedModule'] = $module;
            $this->moduleResult = $module->handleError( eZError::KERNEL_MODULE_NOT_FOUND, 'kernel', array( 'module' => $module_name ) );
        }
        else
        {
            if ( $moduleCheck['view_checked'] )
                eZDebug::writeError( "View '" . $moduleCheck['view'] . "' in module '" . $moduleCheck['module'] . "' is disabled", "index" );
            else
                eZDebug::writeError( "Module '" . $moduleCheck['module'] . "' is disabled", "index" );
            $module = new eZModule( "", "", $moduleCheck['module'] );
            $GLOBALS['eZRequestedModule'] = $module;
            $this->moduleResult = $module->handleError( eZError::KERNEL_MODULE_DISABLED, 'kernel', array( 'check' => $moduleCheck ) );
        }
        $this->moduleRunRequired = false;
        if ( $module->exitStatus() == eZModule::STATUS_RERUN )
        {
            if ( isset( $this->moduleResult['rerun_uri'] ) )
            {
                $this->uri = eZURI::instance( $this->moduleResult['rerun_uri'] );
                $this->moduleRunRequired = true;
            }
            else
                eZDebug::writeError( 'No rerun URI specified, cannot continue', 'index.php' );
        }

        if ( is_array( $this->moduleResult ) )
        {
            if ( isset( $this->moduleResult["pagelayout"] ) )
            {
                $this->show_page_layout = $this->moduleResult["pagelayout"];
                $GLOBALS['eZCustomPageLayout'] = $this->moduleResult["pagelayout"];
            }
            if ( isset( $this->moduleResult["external_css"] ) )
                $this->use_external_css = $this->moduleResult["external_css"];
        }
    }

    return $module;
  }

  private function initializeUserInfoIf($condition)
  {
    if($condition)
    {
      $this->currentUser = eZUser::currentUser();
      $this->wwwDir = eZSys::wwwDir();
      $this->userIsLoggedIn($this->currentUser);
    }
  }

  private function userIsLoggedIn($currentUser)
  {
    // On host based site accesses this can be empty, causing the cookie to be set for the current dir,
    // but we want it to be set for the whole eZ publish site
    $cookiePath = $this->wwwDir != '' ? $this->wwwDir : '/';

    if ( $currentUser->isLoggedIn() )
    {
        setcookie( 'is_logged_in', 'true', 0, $cookiePath );
        header( 'Etag: ' . $currentUser->attribute( 'contentobject_id' ) );
    }
    else if ( isset( $_COOKIE['is_logged_in'] ) )
    {
        setcookie( 'is_logged_in', false, 0, $cookiePath );
    }
  }

  private function redirect()
  {
    ob_start();
    
    $GLOBALS['eZRedirection'] = true;
    $this->ini = eZINI::instance();
    $automatic_redir = true;

    if ( $GLOBALS['eZDebugAllowed'] && ( $redirUri = $this->ini->variable( 'DebugSettings', 'DebugRedirection' ) ) != 'disabled' )
    {
        if ( $redirUri == "enabled" )
        {
            $automatic_redir = false;
        }
        else
        {
            $redirUris = $this->ini->variableArray( "DebugSettings", "DebugRedirection" );
            $this->uri = eZURI::instance( eZSys::requestURI() );
            $this->uri->toBeginning();
            foreach ( $redirUris as $redirUri )
            {
                $redirUri = new eZURI( $redirUri );
                if ( $redirUri->matchBase( $this->uri ) )
                {
                    $automatic_redir = false;
                    break;
                }
            }
        }
    }

    $redirectURI = eZSys::indexDir();

    $moduleRedirectUri = $this->module->redirectURI();
    $redirectStatus = $this->module->redirectStatus();
    $translatedModuleRedirectUri = $moduleRedirectUri;
    if ( $this->ini->variable( 'URLTranslator', 'Translation' ) == 'enabled' &&
         eZURLAliasML::urlTranslationEnabledByUri( new eZURI( $moduleRedirectUri ) ) )
    {
        if ( eZURLAliasML::translate( $translatedModuleRedirectUri, true ) )
        {
            $moduleRedirectUri = $translatedModuleRedirectUri;
            if ( strlen( $moduleRedirectUri ) > 0 and
                 $moduleRedirectUri[0] != '/' )
                $moduleRedirectUri = '/' . $moduleRedirectUri;
        }
    }

    if ( preg_match( '#^(\w+:)|^//#', $moduleRedirectUri ) )
    {
        $redirectURI = $moduleRedirectUri;
    }
    else
    {
        $leftSlash = false;
        $rightSlash = false;
        if ( strlen( $redirectURI ) > 0 and
             $redirectURI[strlen( $redirectURI ) - 1] == '/' )
            $leftSlash = true;
        if ( strlen( $moduleRedirectUri ) > 0 and
             $moduleRedirectUri[0] == '/' )
            $rightSlash = true;

        if ( !$leftSlash and !$rightSlash ) // Both are without a slash, so add one
            $moduleRedirectUri = '/' . $moduleRedirectUri;
        else if ( $leftSlash and $rightSlash ) // Both are with a slash, so we remove one
            $moduleRedirectUri = substr( $moduleRedirectUri, 1 );
        $redirectURI .= $moduleRedirectUri;
    }

    eZStaticCache::executeActions();

    eZDB::checkTransactionCounter();

    if ( $automatic_redir )
    {
        eZHTTPTool::redirect( $redirectURI, array(), $redirectStatus );
    }
    else
    {
        // Make sure any errors or warnings are reported
        if ( $this->ini->variable( 'DebugSettings', 'DisplayDebugWarnings' ) == 'enabled' )
        {
            if ( isset( $GLOBALS['eZDebugError'] ) and
                 $GLOBALS['eZDebugError'] )
            {
                $this->eZAppendWarningItem( array( 'error' => array( 'type' => 'error',
                                                              'number' => 1,
                                                              'count' => $GLOBALS['eZDebugErrorCount'] ),
                                            'identifier' => 'ezdebug-first-error',
                                            'text' => ezi18n( 'index.php', 'Some errors occurred, see debug for more information.' ) ) );
            }

            if ( isset( $GLOBALS['eZDebugWarning'] ) and
                 $GLOBALS['eZDebugWarning'] )
            {
                $this->eZAppendWarningItem( array( 'error' => array( 'type' => 'warning',
                                                              'number' => 1,
                                                              'count' => $GLOBALS['eZDebugWarningCount'] ),
                                            'identifier' => 'ezdebug-first-warning',
                                            'text' => ezi18n( 'index.php', 'Some general warnings occured, see debug for more information.' ) ) );
            }
        }
        require_once( "kernel/common/template.php" );
        $tpl = templateInit();
        if ( count( $this->warningList ) == 0 )
            $this->warningList = false;
        $tpl->setVariable( 'site', $this->site );
        $tpl->setVariable( 'warning_list', $this->warningList );
        $tpl->setVariable( 'redirect_uri', eZURI::encodeURL( $redirectURI ) );
        $templateResult = $tpl->fetch( 'design:redirect.tpl' );

        eZDebug::addTimingPoint( "End" );

        self::eZDisplayResult( $templateResult );
    }

    $out = ob_get_contents();
    ob_end_clean();
    ob_end_flush();

    eZExecution::cleanup();
    eZExecution::setCleanExit();
    
    return trim($out);
  }

  private function storeLastUri()
  {
    $currentURI = $this->completeRequestedURI;
    if ( strlen( $currentURI ) > 0 and $currentURI[0] != '/' )
        $currentURI = '/' . $currentURI;

    $lastAccessedURI = "";
    $lastAccessedViewURI = "";

    $http = eZHTTPTool::instance();

    // Fetched stored session variables
    if ( $http->hasSessionVariable( "LastAccessesURI" ) )
    {
        $lastAccessedViewURI = $http->sessionVariable( "LastAccessesURI" );
    }
    if ( $http->hasSessionVariable( "LastAccessedModifyingURI" ) )
    {
        $lastAccessedURI = $http->sessionVariable( "LastAccessedModifyingURI" );
    }

    // Update last accessed view page
    if ( $currentURI != $lastAccessedViewURI and
         !in_array( $this->module->uiContextName(), array( 'edit', 'administration', 'browse', 'authentication' ) ) )
    {
        $http->setSessionVariable( "LastAccessesURI", $currentURI );
    }

    // Update last accessed non-view page
    if ( $currentURI != $lastAccessedURI )
    {
        $http->setSessionVariable( "LastAccessedModifyingURI", $currentURI );
    }
  }

  private function fixModuleResult()
  {
    if(  !is_array( $this->moduleResult )  )
    {
      eZDebug::writeError( 'Module did not return proper result: ' . $this->module->attribute( 'name' ), 'index.php' );
      $this->moduleResult = array();
      $this->moduleResult['content'] = false;
    }

    if ( !isset( $this->moduleResult['ui_context'] ) )
    {
        $this->moduleResult['ui_context'] = $this->module->uiContextName();
    }

    $this->moduleResult['ui_component'] = $this->module->uiComponentName();
  }

  private function show()
  {
    require_once( "kernel/common/template.php" );
    $tpl = templateInit();
    if ( $tpl->hasVariable( 'node' ) )
        $tpl->unsetVariable( 'node' );

    if ( !isset( $this->moduleResult['path'] ) )
        $this->moduleResult['path'] = false;
    $this->moduleResult['uri'] = eZSys::requestURI();

    $tpl->setVariable( "module_result", $this->moduleResult );

    $meta = $this->ini->variable( 'SiteSettings', 'MetaDataArray' );

    if ( !isset( $meta['description'] ) )
    {
        $metaDescription = "";
        if ( isset( $this->moduleResult['path'] ) and
             is_array( $this->moduleResult['path'] ) )
        {
            foreach ( $this->moduleResult['path'] as $pathPart )
            {
                if ( isset( $pathPart['text'] ) )
                    $metaDescription .= $pathPart['text'] . " ";
            }
        }
        $meta['description'] = $metaDescription;
    }

    $this->site['uri'] = $this->oldURI;
    $this->site['redirect'] = false;
    $this->site['meta'] = $meta;
    $this->site['version'] = eZPublishSDK::version();
    $this->site['page_title'] = $this->module->title();

    $tpl->setVariable( "site", $this->site );

    $ezinfo = array( 'version' => eZPublishSDK::version( true ),
                     'version_alias' => eZPublishSDK::version( true, true ),
                     'revision' => eZPublishSDK::revision() );

    $tpl->setVariable( "ezinfo", $ezinfo );
    if ( isset( $tpl_vars ) and is_array( $tpl_vars ) )
    {
        foreach( $tpl_vars as $tpl_var_name => $tpl_var_value )
        {
            $tpl->setVariable( $tpl_var_name, $tpl_var_value );
        }
    }

    if ( $this->show_page_layout )
    {
        if ( $this->ini->variable( 'DebugSettings', 'DisplayDebugWarnings' ) == 'enabled' )
        {
            // Make sure any errors or warnings are reported
            if ( isset( $GLOBALS['eZDebugError'] ) and
                 $GLOBALS['eZDebugError'] )
            {
                $this->eZAppendWarningItem( array( 'error' => array( 'type' => 'error',
                                                              'number' => 1 ,
                                                              'count' => $GLOBALS['eZDebugErrorCount'] ),
                                            'identifier' => 'ezdebug-first-error',
                                            'text' => ezi18n( 'index.php', 'Some errors occurred, see debug for more information.' ) ) );
            }

            if ( isset( $GLOBALS['eZDebugWarning'] ) and
                 $GLOBALS['eZDebugWarning'] )
            {
                $this->eZAppendWarningItem( array( 'error' => array( 'type' => 'warning',
                                                              'number' => 1,
                                                              'count' => $GLOBALS['eZDebugWarningCount'] ),
                                            'identifier' => 'ezdebug-first-warning',
                                            'text' => ezi18n( 'index.php', 'Some general warnings occured, see debug for more information.' ) ) );
            }
        }

        if ( $this->userObjectRequired )
        {
            $this->currentUser = eZUser::currentUser();

            $tpl->setVariable( "current_user", $this->currentUser );
            $tpl->setVariable( "anonymous_user_id", $this->ini->variable( 'UserSettings', 'AnonymousUserID' ) );
        }
        else
        {
            $tpl->setVariable( "current_user", false );
            $tpl->setVariable( "anonymous_user_id", false );
        }

        $tpl->setVariable( "access_type", $this->access );

        if ( count( $this->warningList ) == 0 )
            $this->warningList = false;
        $tpl->setVariable( 'warning_list', $this->warningList );

        $resource = "design:";
        if ( is_string( $this->show_page_layout ) )
        {
            if ( strpos( $this->show_page_layout, ":" ) !== false )
            {
                $resource = "";
            }
        }
        else
        {
            $this->show_page_layout = "pagelayout.tpl";
        }

        // Set the navigation part
        // Check for navigation part settings
        $navigationPartString = 'ezcontentnavigationpart';
        if ( isset( $this->moduleResult['navigation_part'] ) )
        {
            $navigationPartString = $this->moduleResult['navigation_part'];

            // Fetch the navigation part
        }
        $navigationPart = eZNavigationPart::fetchPartByIdentifier( $navigationPartString );

        $tpl->setVariable( 'navigation_part', $navigationPart );
        $tpl->setVariable( 'uri_string', $this->uri->uriString() );
        if ( isset( $this->moduleResult['requested_uri_string'] ) )
        {
            $tpl->setVariable( 'requested_uri_string', $this->moduleResult['requested_uri_string'] );
        }
        else
        {
            $tpl->setVariable( 'requested_uri_string', $this->actualRequestedURI );
        }

        // Set UI context and component
        $tpl->setVariable( 'ui_context', $this->moduleResult['ui_context'] );
        $tpl->setVariable( 'ui_component', $this->moduleResult['ui_component'] );

        $this->templateResult = $tpl->fetch( $resource . $this->show_page_layout );
    }
  }

  protected function header($string)
  {
    //header( $string );
  }

  public function dispatch()
  {

    self::checkPHPVersion();
    $this->setTimezone();
    self::ignoreUserAbort();

    $this->scriptStartTime = microtime( true );
    $this->use_external_css = true;
    $this->show_page_layout = true;
    $this->moduleRunRequired = true;
    $this->policyCheckRequired = true;
    $this->urlTranslatorAllowed = true;
    $this->validityCheckRequired = false;
    $this->userObjectRequired = true;
    $this->sessionRequired = true;
    $this->dbRequired = true;
    $this->noCacheAdviced = true;
    $this->siteDesignOverride = false;
    $this->policyCheckOmitList = array();
    $this->moduleRepositories = array();
    $this->initializeSiteBasics();
    $this->addGlobal( 'eZSiteBasics', $this->siteBasics, true );
    $this->addGlobal( 'eZRedirection', false );
    self::setErrorReporting();
    eZDebugSetting::setDebugINI( eZINI::instance( 'debug.ini' ) );
    $this->eZUpdateTextCodecSettings();
    $this->eZUpdateDebugSettings();
    $this->ini = eZINI::instance();
    $this->setCorrectTimezone();
    $this->initializeCodePagePermissions();
    eZExecution::addCleanupHandler( array('ezpController', 'eZDBCleanup') );
    eZExecution::addFatalErrorHandler( array('ezpController', 'eZFatalError') );
    eZDebug::setScriptStart( $this->scriptStartTime );
    $this->httpCharset = eZTextCodec::httpCharset();
    $this->ini = eZINI::instance();
    eZLocale::setIsDebugEnabled( (bool) $this->ini->variable( 'RegionalSettings', 'Debug' ) == 'enabled' );
    eZDebug::setHandleType( eZDebug::HANDLE_FROM_PHP );
    $this->addGlobal( 'eZGlobalRequestURI', eZSys::serverVariable( 'REQUEST_URI' ) );
    eZSys::init( 'index.php', $this->ini->variable( 'SiteAccessSettings', 'ForceVirtualHost' ) == 'true' );

    eZDebug::addTimingPoint( "Script start" );
    $this->uri = eZURI::instance( eZSys::requestURI() );
    $this->addGlobal( 'eZRequestedURI', $this->uri );
    self::preCheck();
    self::checkForExtension();
    $this->access = self::access( $this->uri );
    eZDebug::checkDebugByUser();
    eZExtension::activateExtensions( 'access' );
    $this->tplINI = eZINI::instance( 'template.ini' );
    $this->tplINI->loadCache();
    self::initializeCallBackForSession($this->ini);
    $this->moduleRepositories = eZModule::activeModuleRepositories();
    eZModule::setGlobalPathList( $this->moduleRepositories );
    $check = eZHandlePreChecks( $this->siteBasics, $this->uri );
    $this->db = false;
    $this->initializeDatabaseAndStartSession();
    $this->initializeLocale();
    
    $this->headerList = array( 'Expires'          => 'Mon, 26 Jul 1997 05:00:00 GMT',
                               'Last-Modified'    => gmdate( 'D, d M Y H:i:s' ) . ' GMT',
                               'Cache-Control'    => 'no-cache, must-revalidate',
                               'Pragma'           => 'no-cache',
                               'X-Powered-By'     => 'eZ Publish',
                               'Content-Type'     => 'text/html; charset=' . $this->httpCharset,
                               'Served-by'        => $_SERVER["SERVER_NAME"],
                               'Content-language' => $this->languageCode );

    
    $this->site = array( 'title'      => $this->ini->variable( 'SiteSettings', 'SiteName' ),
                         'design'     => $this->ini->variable( 'DesignSettings', 'SiteDesign' ),
                         'http_equiv' => array( 'Content-Type'    => 'text/html; charset=' . $this->httpCharset,
                                                'Content-language' => $this->languageCode ) );
    

    $this->headerList = array_merge( $this->headerList, eZHTTPHeader::headerOverrideArray( $this->uri ) );

    foreach( $this->headerList as $key => $value )
    {
      $this->header( $key . ': ' . $value );
    }

    eZSection::initGlobalID();

    $this->policyCheckViewMap = array();
    $this->readRoleSettings();
    
    $this->module = $this->moduleLoop($check);

    $this->initializeUserInfoIf($this->ini->variable( "SiteAccessSettings", "CheckValidity" ) !== 'true');
    
    switch($this->module->exitStatus())
    {
      case eZModule::STATUS_REDIRECT:
        return $this->redirect();
      case eZModule::STATUS_OK:
      default:
        $this->storeLastUri();
        break;
    }
    
    eZDebug::addTimingPoint( "Module end '" . $this->module->attribute( 'name' ) . "'" );
    $this->fixModuleResult();
    eZDebug::setUseExternalCSS( $this->use_external_css );

    $this->templateResult = $this->moduleResult['content'];

    if ($this->show_page_layout)
    {
      $this->show();
    }

    eZDebug::addTimingPoint( "End" );

    eZDB::checkTransactionCounter();
    eZExecution::cleanup();
    
    return self::eZDisplayResult( $this->templateResult );
  }
}