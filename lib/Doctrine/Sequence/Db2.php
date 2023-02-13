<?php
/*
 *  $Id: Db2.php 7490 2010-03-29 19:53:27Z jwage $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

/**
 * Doctrine_Sequence_Db2
 *
 * @package     Doctrine
 * @subpackage  Sequence
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @version     $Revision: 7490 $
 */
class Doctrine_Sequence_Db2 extends Doctrine_Sequence
{
    /**
     * Returns the next free id of a sequence
     *
     * @param string $seqName   name of the sequence
     * @param bool   $onDemand  when true missing sequences are automatic created
     *
     * @return integer          next id in the given sequence
     * @throws Doctrine_Sequence_Exception
     */
    public function nextId($seqName, $onDemand = true)
    {
        $sequenceName = $this->conn->quoteIdentifier($this->conn->formatter->getSequenceName($seqName), true);
        $query        = 'SELECT NEXTVAL FOR ' . $sequenceName . ' AS VAL FROM SYSIBM.SYSDUMMY1';

        try {
            $result = $this->conn->fetchOne($query);
            $result = ($result) ? $result['VAL'] : null;
        } catch (Doctrine_Connection_Exception $e) {
            if ($onDemand && $e->getPortableCode() == Doctrine_Core::ERR_NOSUCHTABLE) {
                try {
                    $result = $this->conn->export->createSequence($seqName);
                } catch (Doctrine_Exception $e) {
                    throw new Doctrine_Sequence_Exception('on demand sequence ' . $seqName . ' could not be created');
                }

                return $this->nextId($seqName, false);
            } else {
                throw new Doctrine_Sequence_Exception('sequence ' . $seqName . ' does not exist');
            }
        }
        return $result;
    }

    /**
     * Return the most recent value from the specified sequence in the database.
     * This is supported only on RDBMS brands that support sequences
     * (e.g. Oracle, PostgreSQL, DB2).  Other RDBMS brands return null.
     *
     * @param string $sequenceName
     * @return integer|null
     * @throws Doctrine_Adapter_Db2_Exception
     */
    public function currId($sequenceName)
    {
        $sql = 'SELECT PREVVAL FOR '
             . $this->conn->quoteIdentifier($this->conn->formatter->getSequenceName($sequenceName))
             . ' AS VAL FROM SYSIBM.SYSDUMMY1';

        $stmt   = $this->conn->standaloneQuery($sql);
        $result = $stmt->fetchAll(Doctrine_Core::FETCH_ASSOC);
        if ($result) {
            return $result[0]['VAL'];
        } else {
            return null;
        }
    }

    /**
     * Gets the last ID generated automatically by an IDENTITY/AUTOINCREMENT column.
     *
     * As a convention, on RDBMS brands that support sequences
     * (e.g. Oracle, PostgreSQL, DB2), this method forms the name of a sequence
     * from the arguments and returns the last id generated by that sequence.
     * On RDBMS brands that support IDENTITY/AUTOINCREMENT columns, this method
     * returns the last value generated for such a column, and the table name
     * argument is disregarded.
     *
     * The IDENTITY_VAL_LOCAL() function gives the last generated identity value
     * in the current process, even if it was for a GENERATED column.
     *
     * @param string $tableName OPTIONAL
     * @param string $primaryKey OPTIONAL
     * @return integer|null
     * @throws Doctrine_Adapter_Db2_Exception
     */
    public function lastInsertId($tableName = null, $primaryKey = null)
    {
        $this->_connect();

        if ($tableName !== null) {
            $sequenceName = $tableName;
            if ($primaryKey) {
                $sequenceName .= "_$primaryKey";
            }
            $sequenceName .= '_seq';
            return $this->lastSequenceId($sequenceName);
        }

        $sql    = 'SELECT IDENTITY_VAL_LOCAL() AS VAL FROM SYSIBM.SYSDUMMY1';
        $stmt   = $this->conn->standaloneQuery($sql);
        $result = $stmt->fetchAll(Doctrine_Core::FETCH_ASSOC);
        if ($result) {
            return $result[0]['VAL'];
        } else {
            return null;
        }
    }
}
