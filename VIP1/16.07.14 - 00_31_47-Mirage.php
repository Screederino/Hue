<?php exit() ?>--by Mirage 70.77.113.157
local wp = WayPointManager()

function OnTick()
 if IsKeyDown(string.byte('O')) then
  for i = 1, heroManager.iCount do
   local hero = heroManager:getHero(i)

   if wp:GetWayPointChangeRate(hero, 1) > 10 then
    print(hero.charName .. ' is a BoL user. HL3 confirmed.')
   end
  end
 end
end