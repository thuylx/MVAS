#!/usr/bin/python
import sys
import mysqlobj

MysqlObj = mysqlobj.MysqlObj(sys.argv[1])
MysqlObj.printRows()
MysqlObj.printCol("SCHEMA_NAME")
