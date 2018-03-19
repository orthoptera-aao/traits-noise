library(tuneR)
library(seewave)
library(inflection)

args = commandArgs(trailingOnly=TRUE)
recording_id <- args[1]
filename <- args[2];

foder <- "modules/traits-noise/noise/"

wave <- readWave(paste0(filename));
wave <- normalize(wave);

values <- wave@left

h <- hist(abs(values),breaks=10000, plot=FALSE)

inflection <- bede(h$mids, h$counts, 1)
inflection_used <- inflection$iplast * 5

png(filename=paste0(folder,recording_id,".hist.png"))
plot(h$mids, h$counts)
abline(v=inflection_used, col="green")
dev.off()

lower <- abs(values) < inflection_used

runs <- rle(lower)
runs_cumpos <- c(0,cumsum(runs$lengths))

regions <- runs$lengths[which(runs$values == TRUE)]
regions_cumpos <- runs_cumpos[which(runs$values == TRUE)]

longest <- which(regions > 22000)


xleft <- regions_cumpos[longest] / wave@samp.rate
xright <- (regions_cumpos[longest] + regions[longest]) / wave@samp.rate

f <- cbind(xleft, xright)
write.csv(f, paste0(folder,recording_id,".csv"))

png(filename=paste0(folder,recording_id,".spectro.png"))
oscillo(wave)
abline(h=c(inflection_used, -inflection_used),col="blue")
rect(xleft, -1, xright, 1, col=rgb(1,0,0,alpha=0.2))
dev.off()
