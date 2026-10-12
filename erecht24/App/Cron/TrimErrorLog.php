<?php
/**
 * Compatibility shim for updates from the legacy 3.x plugin. Never loaded by this plugin itself.
 *
 * Plugin 3.3.x hooks Inc\Core\Updater::execute() to `upgrader_process_complete`.
 * When 3.x is updated in place to 4.x, that callback is still in memory and runs
 * after the files have been replaced. It calls Deactivator::unregisterCron(), which
 * reads TrimErrorLog::CRON_ACTION_IDENTIFIER. The old Composer autoloader then
 * resolves the class to exactly this path; without this file the update ends in
 * "Class eRecht24\LegalTexts\App\Cron\TrimErrorLog not found".
 *
 * Keep the namespace, path and constant unchanged.
 *
 * @package ERecht24LegalText
 */

namespace eRecht24\LegalTexts\App\Cron;

defined( 'ABSPATH' ) || exit;

/**
 * Provides the constant the legacy 3.x update routine expects.
 */
final class TrimErrorLog {

	const CRON_ACTION_IDENTIFIER = 'eRecht24_trim_errorlog_cron';
}
