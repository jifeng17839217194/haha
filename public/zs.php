<?php
//臻识测试
$serialData = hex2bin("0064FFFF0006313539373533FB06");
$dataLen = strlen($serialData);
echo '{
                          "Response_AlarmInfoPlate": {
                          "info": "ok",
                          "channelNum":0,
                          "serialData": [
                                  {
                                      "serialChannel": 0,
                                      "data": "' . base64_encode($serialData) . '",
                                      "dataLen": ' . $dataLen . '
                                  }
                              ]
                          }
                       }';
sleep(2);