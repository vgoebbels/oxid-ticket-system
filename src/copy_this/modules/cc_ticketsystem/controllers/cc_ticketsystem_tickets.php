<?php
/**
 * CommerceCoding Ticketsystem for OXID eShop
 *
 * NOTICE OF LICENSE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 3 of the License
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/
 *
 * @copyright   Copyright (c) 2012 CommerceCoding (http://www.commerce-coding.de)
 * @author      Alexander Diebler
 * @license     http://opensource.org/licenses/GPL-3.0  GNU General Public License, version 3 (GPL-3.0)
 */

class cc_ticketsystem_tickets extends Shop_Config {

  /**
   * Template for detailed ticket view.
   *
   * @var string
   */
  protected $_sThisTicketTemplate = 'cc_ticketsystem_ticket.tpl';

  /**
   * Template for overview of all tickets.
   *
   * @var string
   */
  protected $_sThisOverviewTemplate = 'cc_ticketsystem_overview.tpl';

  /**
   * List with all users tickets.
   *
   * @var array
   */
  protected $_aTicketList = null;

  /**
   * Returns the right template and prepares the view data.
   *
   * @return string
   */
  public function render() {

    if(isset($_GET['ticket'])) {

      $ticket = oxNew('cc_ticket');
      $ticket->load($_GET['ticket']);
      $this->_aViewData['ticket'] = $ticket;
      $this->_aViewData['ticket_texts'] = $ticket->getTextList();

      return $this->_sThisTicketTemplate;
    }

    $tickets = $this->getTicketList();
    $this->_aViewData['admin_tickets'] = $tickets[cc_ticket::STATE_ADMIN_ACTION];
    $this->_aViewData['user_tickets'] = $tickets[cc_ticket::STATE_USER_ACTION];
    $this->_aViewData['closed_tickets'] = $tickets[cc_ticket::STATE_CLOSED];

    return $this->_sThisOverviewTemplate;
  }

  /**
   * Selects all tickets from the database and returns it.
   *
   * @return array
   */
  public function getTicketList() {

    if ( $this->_aTicketList !== null ) {
      return $this->_aTicketList;
    }

    $sSelect = "SELECT * FROM cctickets";
    $sSelect .= " ORDER BY UPDATED DESC";

    $oTicketList = oxNew('oxList');
    $oTicketList->init('cc_ticket');
    $oTicketList->selectString($sSelect);
    $this->_aTicketList = $this->_prepareForTemplate($oTicketList->getArray());

    return $this->_aTicketList;
  }

  /**
   * Prepares the raw ticket data by dividing them on the basis of the state.
   *
   * @param object $aTicketList result array with all tickets
   * @return array
   */
  protected function _prepareForTemplate($aTicketList) {

    $aTickets = array();

    foreach($aTicketList as $oTicket) {
      $sTicketId = $oTicket->cctickets__oxid->rawValue;
      $aTickets[$oTicket->cctickets__state->rawValue][$sTicketId]['user'] = $oTicket->cctickets__oxuserid->rawValue;
      $aTickets[$oTicket->cctickets__state->rawValue][$sTicketId]['subject'] = $oTicket->cctickets__subject->rawValue;
      $aTickets[$oTicket->cctickets__state->rawValue][$sTicketId]['updated'] = $oTicket->cctickets__updated->rawValue;
    }

    return $aTickets;
  }

  /**
   * Updates the selected ticket and informs the user.
   *
   * @return string
   */
  public function updateTicket() {

    $sText = trim((string)oxConfig::getParameter('tickettext', true));
    $sTicketId = trim((string)oxConfig::getParameter('oxid', true));
    $sTimestamp = date('Y-m-d H:i:s');

    $oTicket = oxNew('cc_ticket');
    $oTicket->load($sTicketId);
    $oTicket->cctickets__state   = new oxField($oTicket::STATE_USER_ACTION);
    $oTicket->cctickets__updated = new oxField($sTimestamp);
    $oTicket->save();

    $oTicketText = oxNew('cc_tickettext');
    $oTicketText->cctickettexts__ticketid  = new oxField($sTicketId);
    $oTicketText->cctickettexts__text      = new oxField($sText);
    $oTicketText->cctickettexts__timestamp = new oxField($sTimestamp);
    $oTicketText->cctickettexts__author    = new oxField($oTicket::AUTHOR_ADMIN);
    $oTicketText->save();

    $oEmail = oxNew('cc_oxemail');
    $oEmail->sendTicketUpdateToUser($oTicket, $sText);

    return '?cl=' . __CLASS__ . '&ticket=' . $sTicketId;
  }

  /**
   * Locks a ticket and returns admin to the ticket overview.
   *
   * @return string
   */
  public function lock() {

    $sTicketId = trim((string)$_GET['ticket']);

    $oTicket = oxNew('cc_ticket');
    $oTicket->load($sTicketId);
    $oTicket->cctickets__state   = new oxField($oTicket::STATE_CLOSED);
    $oTicket->save();

    return '?cl=' . __CLASS__;
  }

  /**
   * Unlocks a ticket and returns admin to the ticket overview.
   *
   * @return string
   */
  public function unlock() {

    $sTicketId = trim((string)$_GET['ticket']);

    $oTicket = oxNew('cc_ticket');
    $oTicket->load($sTicketId);
    $oTicket->cctickets__state   = new oxField($oTicket::STATE_ADMIN_ACTION);
    $oTicket->save();

    return '?cl=' . __CLASS__;
  }
}