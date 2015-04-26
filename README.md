QueueingStudyInterface
======================

#### Demo CARL Interface for the RMS

This repository contains an interface for the [Robot Management System (RMS)](https://github.com/WPI-RAIL/rms).

This project is released as part of the [Robot Web Tools](http://robotwebtools.org/) effort.

## Queueing

RMS Interface for a User Study with CARL. The interface communicates with rms_queue_manager to handle users in a queue. Other features include first-time instructions, and feedback from actions. 

### Setup
To setup the interface on a server running the RMS, run the automated script in the [install](install) directory:

```bash
cd install
./install.bash
```

This script will copy the scripts to your local RMS directory. Afterwards, you will be able to add the interface through the admin panel.

### Build
Checkout [utils/README.md](utils/README.md) for details on building if you are contributing code.

### License
QueueingStudyInterface is released with a BSD license. For full terms and conditions, see the [LICENSE](LICENSE) file.

### Authors
See the [AUTHORS](AUTHORS.md) file for a full list of contributors.
