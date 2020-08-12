# Arduino solar powered GSM Tap Water system

Guide into creating a solar and battery powered tap water system, allowing for remote commanding via GSM (2G) network.

<img src="https://manueldeprada.com/blog/assets/main.jpg" style="zoom:10%" />
## Background

This project may be useful to someone trying to solve some of the challenges of this problem. In fact, the tap system was the most trivial part of the project while the GSM communication and battery operation where the most difficult ones.

This type of systems may be needed in rural areas, where no AC current, wifi or broadband communication systems are available.

## Hardware components

1. Arduino UNO.

2. Relay Module (x2).

3. GSM SIM800L module.

4. DC-DC Buck converter module (x2).

5. 10W 21V solar panel.

6. LiPo 18650 batteries (x6).

7. 3S BMS Lithium battery protection PCB.

8. 40kΩ and 80kΩ resistors.

9. LM35 temperature sensor.

10. 12V 3/4" DC electric ball valve.

## Wiring and connections

![](https://manueldeprada.com/blog/assets/scheme.png)

    ## Parts of the system

### Powering

I chose a 3 series 18650 battery cell configuration, that gives us 8 to 12V and 6600mAh of power. Each 18650 cell gives between 2.5 and 4.2V and 3350mAh, so 3 in series to have enough voltage and 2 in parallel to have enough capacity seem a reasonable choice. To recharge the batteries, I use a small 10W solar panel that gives 21V to 12V with daylight. Keep in mind that your solar panel should be reverse current protected so it doesn't draw current from the batteries when it can't provide enough voltage. 

The buck converter is used to stabilise the voltage from the solar panels and also to limit the charge capacity of the cells to around 90% (12V instead of 12.6V). 

Make sure you buy trusted 18650 power cells and not some Chinese fake clone from AliExpress. A good reliable source of batteries is [nkon.nl](). Thanks to [@Charlio99](https://github.com/Charlio99) for helping me with the power setup.

#### Reading the battery status

To read the battery charge status, one must know the voltage of the battery. It's oscillating between 7.5V when fully discharged and 12.6V when fully charged. To maximize the batteries lifespan, we will try to keep them between 8 and 12V. 

The Arduino board has analog input pins that read voltage from 0 to 5V. We need to convert the 7.5 to 12.6V to something below 5V, so the Arduino can read it. For that, we use a voltage divider with 2 resistors, we need that 15V become 5V:

![](https://manueldeprada.com/blog/assets/v_divider.png)

You can choose whatever resistor values you have available, given R1=2R2. The higher the value, the less current it will consume. But also, do not exceed 100kΩ since you will not get accurate readings of the voltage, for a reason I still do not understand.

### Water tap

The water tap is essentially a DC motor that works with the 12V from the batteries. I just connect the ground to the batteries and I open or close the tap by switching the correct wire to 12V with the relays. Turning the relay module on or off is as simple as writing high or low value to a digital output pin with the arduino.

### Temperature Sensor

The LM35 temperature sensor just needs a 5V input and it will output a 0-5V analog voltage value that represents the current temperature. That will be useful since the system will be enclosed in a waterproof environment and batteries are very sensitive to temperature. We can also shut down operation or send an alert if a battery malfunctions and heats up the system.

### GSM communication

The SIM800L module enables us to send and receive data from a server. It requires a 3.4-4.4V input, so we can't use the 5V or 3.3V ports of the Arduino and we need a DC-DC buck converter to get a stable, well regulated 4V power supply. 

It communicates with the Arduino via serial interface. We could use the dedicated 0 and 1 pins, but since we need that pins for debugging speed is not key for us, we will be using a software emulated serial interface with the digital pins 7 and 8.

## Software

