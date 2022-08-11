// SPDX-License-Identifier: GPL-3.0

pragma solidity >=0.7.0 <0.9.0;

contract StorageStr {

    string data;

    constructor(string memory s) {
        data = s;
    }

    function update(string memory s) public {
        data = s;
    }

    function get() public view returns (string memory) {
        return data;
    }

}

