<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2015 Purdue University. All rights reserved.
 *
 * This file is part of: The HUBzero(R) Platform for Scientific Collaboration
 *
 * The HUBzero(R) Platform for Scientific Collaboration (HUBzero) is free
 * software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * HUBzero is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @author    Christopher Smoak <csmoak@purdue.edu>
 * @copyright Copyright 2005-2015 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// No direct access
defined('_HZEXEC_') or die();

/**
 * Cron plugin for support tickets
 */
class plgCronGroups extends \Hubzero\Plugin\Plugin
{
	/**
	 * Return a list of events
	 *
	 * @return  array
	 */
	public function onCronEvents()
	{
		$this->loadLanguage();

		$obj = new stdClass();
		$obj->plugin = $this->_name;
		$obj->events = array(
			array(
				'name'   => 'cleanGroupFolders',
				'label'  => Lang::txt('PLG_CRON_GROUPS_REMOVE_ABANDONED_ASSETS'),
				'params' => ''
			),
			array(
				'name'   => 'sendGroupAnnouncements',
				'label'  => Lang::txt('PLG_CRON_GROUPS_SEND_ANNOUNCEMENTS'),
				'params' => ''
			)
		);

		return $obj;
	}

	/**
	 * Remove unused group folders
	 *
	 * @param   object   $job  \Components\Cron\Models\Job
	 * @return  boolean
	 */
	public function cleanGroupFolders(\Components\Cron\Models\Job $job)
	{
		// get group params
		$groupParameters = Component::params('com_groups');

		// get group upload path
		$groupUploadPath = ltrim($groupParameters->get('uploadpath', '/site/groups'), DS);

		// get group folders
		$groupFolders = Filesystem::directories(PATH_APP . DS . $groupUploadPath);

		// loop through each group folder
		foreach ($groupFolders as $groupFolder)
		{
			// load group object for each folder
			$hubzeroGroup = \Hubzero\User\Group::getInstance(trim($groupFolder));

			// if we dont have a group object delete folder
			if (!is_object($hubzeroGroup))
			{
				// delete folder
				Filesystem::delete(PATH_APP . DS . $groupUploadPath . DS . $groupFolder);
			}
		}

		// job is no longer active
		return true;
	}


	/**
	 * Send scheduled group announcements
	 *
	 * @param   object   $job  \Components\Cron\Models\Job
	 * @return  boolean
	 */
	public function sendGroupAnnouncements(\Components\Cron\Models\Job $job)
	{
		$database = JFactory::getDBO();

		// get hubzero announcement object
		$hubzeroAnnouncement = new \Hubzero\Item\Announcement($database);

		// get all announcements that are not yet sent but want to be mailed
		$announcements = $hubzeroAnnouncement->find(array('email' => 1,'sent' => 0));

		// loop through each announcement
		foreach ($announcements as $announcement)
		{
			// load the announcement object
			$hubzeroAnnouncement->load($announcement->id);

			// check to see if we can send
			if ($hubzeroAnnouncement->announcementPublishedForDate())
			{
				// email announcement
				$hubzeroAnnouncement->emailAnnouncement();

				// mark as sent
				$hubzeroAnnouncement->sent = 1;
				$hubzeroAnnouncement->save($hubzeroAnnouncement);
			}
		}
		return true;
	}
}

