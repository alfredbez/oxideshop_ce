#A quick way to set english as the default for eShop demodata

#Set English as default language
update oxconfig set oxvarvalue=0xDDC1 where oxvarname='sDefaultLang';
