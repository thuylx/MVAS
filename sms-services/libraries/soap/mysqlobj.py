#!/usr/bin/python
# MySQL-python

import sys
import dbobj
import MySQLdb;

class MysqlObj(dbobj.DbObj):
  def __init__(
    self,
    query=""
  ):
#
    host = "localhost"
    user = "root"
    passwd = ""
    db = "mysql"
#
    dbobj.DbObj.__init__(self,host,user,passwd,db)
    self.query=query
    self.rows = {}
    try:
      conn = MySQLdb.connect(
        host=self.host,
        user=self.user,
        passwd=self.passwd,
        db=self.db
      )
    except MySQLdb.Error, e:
      print("Error %d %s" % (e.args[0], e.args[1]))
      sys.exit(1)
    cursor = conn.cursor(MySQLdb.cursors.DictCursor)
    cursor.execute(self.query)
    result_set = cursor.fetchall ()
    for row in result_set:
      self.rows[len(self.rows)] = row
    cursor.close()
    conn.close()
    return

