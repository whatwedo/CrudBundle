<?php

/*
 * Copyright (c) 2017, whatwedo GmbH
 * All rights reserved
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace whatwedo\CrudBundle\Encoder;

use Prophecy\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Encoder\CsvEncoder;

class WhatwedoCsvEncoder extends CsvEncoder
{

    private $delimiter;
    private $enclosure;
    private $escapeChar;
    private $keySeparator;

    private $headers;

    /**
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escapeChar
     * @param string $keySeparator
     */
    public function __construct($delimiter = ',', $enclosure = '"', $escapeChar = '\\', $keySeparator = '.')
    {
        parent::__construct($delimiter, $enclosure, $escapeChar, $keySeparator);
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escapeChar = $escapeChar;
        $this->keySeparator = $keySeparator;
    }

    /**
     * {@inheritdoc}
     */
    public function encode($data, $format, array $context = array())
    {
        $handle = fopen('php://temp,', 'w+');

        if (!is_array($data)) {
            $data = array(array($data));
        } elseif (empty($data)) {
            $data = array(array());
        } else {
            // Sequential arrays of arrays are considered as collections
            $i = 0;
            foreach ($data as $key => $value) {
                if ($i !== $key || !is_array($value)) {
                    $data = array($data);
                    break;
                }

                ++$i;
            }
        }

        $headers = null;
        foreach ($data as $value) {
            $result = array();
            $this->flatten($value, $result);

            if (null === $headers) {
                $headers = array_keys($result);
                fputcsv($handle, $headers, $this->delimiter, $this->enclosure, $this->escapeChar);
            } elseif (array_keys($result) !== $headers) {
                throw new InvalidArgumentException('To use the CSV encoder, each line in the data array must have the same structure. You may want to use a custom normalizer class to normalize the data format before passing it to the CSV encoder.');
            }

            fputcsv($handle, $result, $this->delimiter, $this->enclosure, $this->escapeChar);
        }

        rewind($handle);
        $value = stream_get_contents($handle);
        fclose($handle);

        return $value;
    }
    /**
     * Flattens an array and generates keys including the path.
     *
     * @param array  $array
     * @param array  $result
     * @param string $parentKey
     */
    private function flatten(array $array, array &$result, $parentKey = '')
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $this->flatten($value, $result, $parentKey.$key.$this->keySeparator);
            } else {
                $headerName = $parentKey.$key;
                if (array_key_exists($headerName, $this->headers)) {
                    $headerName = $this->headers[$parentKey.$key];
                }
                $result[$headerName] = $value;
            }
        }
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeaderTransformation(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }

}
