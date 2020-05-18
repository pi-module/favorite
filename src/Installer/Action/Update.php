<?php
/**
 * Pi Engine (http://piengine.org)
 *
 * @link            http://code.piengine.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://piengine.org
 * @license         http://piengine.org/license.txt New BSD License
 */

/**
 * @author Frédéric TISSOT <contact@espritdev.fr>
 */

namespace Module\Favourite\Installer\Action;

use Pi;
use Pi\Application\Installer\Action\Update as BasicUpdate;
use Laminas\EventManager\Event;

class Update extends BasicUpdate
{
    /**
     * {@inheritDoc}
     */
    protected function attachDefaultListeners()
    {
        $events = $this->events;
        $events->attach('update.pre', [$this, 'updateSchema']);
        parent::attachDefaultListeners();

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function updateSchema(Event $e)
    {
        $version = $e->getParam('version');

        // Set message model
        $favouriteModel   = Pi::model('list', 'favourite');
        $favouriteTable   = $favouriteModel->getTable();
        $favouriteAdapter = $favouriteModel->getAdapter();

        $status = true;
        if (version_compare($version, '1.2.4', '<')) {
            // Alter table field `identity`
            $table = Pi::db()->prefix('list', 'favourite');
            $sql   = sprintf("ALTER TABLE %s ADD `source` ENUM ('WEB', 'MOBILE') NOT NULL DEFAULT  'WEB';", $table);

            try {
                $favouriteAdapter->query($sql, 'execute');
            } catch (\Exception $exception) {
                $this->setResult('db', [
                    'status'  => false,
                    'message' => 'Table alter query failed: '
                        . $exception->getMessage(),
                ]);
                return false;
            }
        }

        if (version_compare($version, '1.2.6', '<')) {
            // Alter table field `identity`
            if (Pi::service('module')->isActive('event')) {
                $tableFav = Pi::db()->prefix('list', 'favourite');
                $tableExt = Pi::db()->prefix('extra', 'event');
                $sql      = sprintf("UPDATE %s fav INNER JOIN %s ext on ext.id = fav.item SET module = 'event' WHERE module = 'news'", $tableFav, $tableExt);
                try {
                    $favouriteAdapter->query($sql, 'execute');
                } catch (\Exception $exception) {
                    $this->setResult('db', [
                        'status'  => false,
                        'message' => 'Table alter query failed: '
                            . $exception->getMessage(),
                    ]);
                    return false;
                }
            }
        }
        return true;
    }
}